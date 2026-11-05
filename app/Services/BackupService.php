<?php

namespace App\Services;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;
use ZipArchive;

/**
 * Backups are one zip: the database, every uploaded file (product images, prescriptions) and a manifest.
 * MySQL is dumped in pure PHP (no mysqldump needed); SQLite is a file copy. The zip holds customer data,
 * so it lives in storage/app/backups, which the web server never serves.
 */
class BackupService
{
    public function directory(): string
    {
        File::ensureDirectoryExists($dir = storage_path('app/backups'));

        return $dir;
    }

    /** @return array<int, array{name:string,size:int,at:int}> newest first */
    public function all(): array
    {
        return collect(glob($this->directory().'/nexcart-*.zip') ?: [])
            ->map(fn ($f) => ['name' => basename($f), 'size' => filesize($f), 'at' => filemtime($f)])
            ->sortByDesc('at')->values()->all();
    }

    public static function validName(string $name): bool
    {
        return (bool) preg_match('/^nexcart-\d{8}-\d{6}\.zip$/', $name);
    }

    public function path(string $name): string
    {
        if (! static::validName($name) || ! is_file($p = $this->directory().'/'.$name)) {
            throw new RuntimeException('No such backup.');
        }

        return $p;
    }

    public function create(bool $withUploads = true, ?Connection $db = null): string
    {
        $db ??= DB::connection();
        $driver = $db->getDriverName();
        $target = $this->directory().'/nexcart-'.now()->format('Ymd-His').'.zip';
        $tmp = storage_path('app/backups/.tmp-'.bin2hex(random_bytes(4)));
        File::ensureDirectoryExists($tmp);

        try {
            $zip = new ZipArchive;
            if ($zip->open($target, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Could not create the backup file.');
            }

            if ($driver === 'sqlite') {
                $file = $db->getConfig('database');
                if (! is_file($file)) {
                    throw new RuntimeException('SQLite database file not found.');
                }
                $zip->addFile($file, 'database.sqlite');
                $dbFile = 'database.sqlite';
            } elseif (in_array($driver, ['mysql', 'mariadb'], true)) {
                $dump = $tmp.'/database.sql';
                $this->dumpMysql($db, $dump);
                $zip->addFile($dump, 'database.sql');
                $dbFile = 'database.sql';
            } else {
                throw new RuntimeException("Backups do not support the {$driver} database.");
            }

            $files = 0;
            if ($withUploads) {
                foreach (['public' => storage_path('app/public'), 'private' => storage_path('app/private')] as $label => $root) {
                    $files += $this->addTree($zip, $root, "files/{$label}");
                }
            }

            $zip->addFromString('manifest.json', json_encode([
                'app' => 'NexCart', 'version' => config('nexcart.version'), 'created_at' => now()->toIso8601String(),
                'database' => $dbFile, 'driver' => $driver, 'files' => $files, 'with_uploads' => $withUploads,
            ], JSON_PRETTY_PRINT));
            $zip->close();
        } finally {
            File::deleteDirectory($tmp);
        }

        @chmod($target, 0640);

        return $target;
    }

    /** Keep the newest N backups, delete the rest. @return int how many were removed */
    public function prune(int $keep): int
    {
        $removed = 0;
        foreach (array_slice($this->all(), max(1, $keep)) as $old) {
            $removed += (int) @unlink($this->directory().'/'.$old['name']);
        }

        return $removed;
    }

    /** Put a backup back. Replaces the database and (if present) the uploaded files. */
    public function restore(string $zipPath, ?Connection $db = null): array
    {
        $db ??= DB::connection();
        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('That is not a readable backup file.');
        }
        $manifest = json_decode((string) $zip->getFromName('manifest.json'), true);
        if (($manifest['app'] ?? null) !== 'NexCart') {
            throw new RuntimeException('That file is not a NexCart backup.');
        }
        if (($manifest['driver'] ?? null) !== $db->getDriverName() && ! (in_array($manifest['driver'], ['mysql', 'mariadb'], true) && in_array($db->getDriverName(), ['mysql', 'mariadb'], true))) {
            throw new RuntimeException("This backup is from a {$manifest['driver']} database but the site uses {$db->getDriverName()}.");
        }

        $tmp = storage_path('app/backups/.restore-'.bin2hex(random_bytes(4)));
        File::ensureDirectoryExists($tmp);

        try {
            $zip->extractTo($tmp);
            $zip->close();

            if (is_file($tmp.'/database.sqlite')) {
                $db->disconnect();
                copy($tmp.'/database.sqlite', $db->getConfig('database'));
            } else {
                $this->importSql($db, $tmp.'/database.sql');
            }

            $restored = 0;
            foreach (['public' => storage_path('app/public'), 'private' => storage_path('app/private')] as $label => $root) {
                if (is_dir($tmp."/files/{$label}")) {
                    File::ensureDirectoryExists($root);
                    File::copyDirectory($tmp."/files/{$label}", $root);
                    $restored += count(File::allFiles($tmp."/files/{$label}"));
                }
            }
        } finally {
            File::deleteDirectory($tmp);
        }

        return ['version' => $manifest['version'] ?? '?', 'created_at' => $manifest['created_at'] ?? null, 'files' => $restored];
    }

    private function addTree(ZipArchive $zip, string $root, string $prefix): int
    {
        if (! is_dir($root)) {
            return 0;
        }
        $n = 0;
        foreach (File::allFiles($root, true) as $file) {
            if ($file->getFilename() === '.gitignore') {
                continue;
            }
            $zip->addFile($file->getPathname(), $prefix.'/'.str_replace('\\', '/', $file->getRelativePathname()));
            $n++;
        }

        return $n;
    }

    // ---------- MySQL, in plain PHP ----------

    private function dumpMysql(Connection $db, string $file): void
    {
        $out = fopen($file, 'w');
        fwrite($out, '-- NexCart dump '.now()->toIso8601String()."\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\nSET UNIQUE_CHECKS=0;\n");

        foreach ($db->select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'") as $row) {
            $table = array_values((array) $row)[0];
            $quoted = '`'.str_replace('`', '``', $table).'`';
            $create = array_values((array) $db->selectOne("SHOW CREATE TABLE {$quoted}"))[1];

            fwrite($out, "DROP TABLE IF EXISTS {$quoted};\n".preg_replace('/\s*\R\s*/', ' ', $create).";\n");

            $db->table($table)->orderByRaw('1')->chunk(200, function ($rows) use ($out, $table) {
                fwrite($out, static::insertStatement($table, $rows->map(fn ($r) => (array) $r)->all()));
            });
        }

        fwrite($out, "SET UNIQUE_CHECKS=1;\nSET FOREIGN_KEY_CHECKS=1;\n");
        fclose($out);
    }

    /** One INSERT, one line, binary-safe: every string becomes CONVERT(0x… USING utf8mb4). */
    public static function insertStatement(string $table, array $rows): string
    {
        if (! $rows) {
            return '';
        }
        $cols = array_keys($rows[0]);
        $values = array_map(fn ($row) => '('.implode(',', array_map(static::literal(...), array_values($row))).')', $rows);

        return 'INSERT INTO `'.str_replace('`', '``', $table).'` (`'.implode('`,`', array_map(fn ($c) => str_replace('`', '``', $c), $cols)).'`) VALUES '.implode(',', $values).";\n";
    }

    public static function literal(mixed $v): string
    {
        return match (true) {
            $v === null => 'NULL',
            is_bool($v) => $v ? '1' : '0',
            is_int($v) => (string) $v,
            is_float($v) => rtrim(rtrim(sprintf('%.14F', $v), '0'), '.') ?: '0',
            $v === '' => "''",
            default => 'CONVERT(0x'.bin2hex((string) $v).' USING utf8mb4)',
        };
    }

    private function importSql(Connection $db, string $file): void
    {
        if (! is_file($file)) {
            throw new RuntimeException('The backup has no database dump.');
        }
        $pdo = $db->getPdo();
        $handle = fopen($file, 'r');
        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if ($line !== '' && ! str_starts_with($line, '--')) {
                $pdo->exec($line);   // the dump writes exactly one statement per line
            }
        }
        fclose($handle);
    }
}
