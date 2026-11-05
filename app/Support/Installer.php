<?php

namespace App\Support;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use PDO;
use RuntimeException;

/**
 * Web installer helpers: requirement checks, testing a database, writing .env and migrating.
 * Once the store has been set up a lock file is written and the installer refuses to run again,
 * so nobody can re-point a live site at another database.
 */
class Installer
{
    public const EXTENSIONS = ['mbstring', 'openssl', 'pdo', 'fileinfo', 'tokenizer', 'xml', 'ctype', 'json', 'curl', 'gd', 'zip'];

    public static function envPath(): string
    {
        return config('nexcart.env_path') ?: base_path('.env');
    }

    public static function lockPath(): string
    {
        return config('nexcart.lock_file') ?: storage_path('app/installed.lock');
    }

    public static function lock(): void
    {
        File::ensureDirectoryExists(dirname(static::lockPath()));
        file_put_contents(static::lockPath(), now()->toIso8601String());
    }

    public static function locked(): bool
    {
        return is_file(static::lockPath());
    }

    /** True when there is no usable database yet (cannot connect, or the tables are missing). */
    public function needsDatabase(): bool
    {
        try {
            DB::connection()->getPdo();

            return ! Schema::hasTable('settings');
        } catch (\Throwable) {
            return true;
        }
    }

    public function available(): bool
    {
        return ! static::locked() && $this->needsDatabase();
    }

    /** @return array<int, array{label:string,ok:bool,required:bool,detail:string}> */
    public function requirements(): array
    {
        $rows = [['label' => 'PHP 8.3+', 'ok' => version_compare(PHP_VERSION, '8.3.0', '>='), 'required' => true, 'detail' => PHP_VERSION]];

        foreach (static::EXTENSIONS as $ext) {
            $rows[] = ['label' => "PHP extension: {$ext}", 'ok' => extension_loaded($ext), 'required' => true, 'detail' => extension_loaded($ext) ? 'OK' : 'missing'];
        }
        $rows[] = ['label' => 'A database driver (pdo_mysql or pdo_sqlite)', 'ok' => extension_loaded('pdo_mysql') || extension_loaded('pdo_sqlite'), 'required' => true, 'detail' => implode(', ', array_filter([extension_loaded('pdo_mysql') ? 'MySQL' : null, extension_loaded('pdo_sqlite') ? 'SQLite' : null])) ?: 'none'];

        $env = static::envPath();
        foreach ([storage_path() => 'storage/', base_path('bootstrap/cache') => 'bootstrap/cache/', is_file($env) ? $env : dirname($env) => is_file($env) ? '.env' : 'project folder (to create .env)'] as $path => $label) {
            $rows[] = ['label' => "Writable: {$label}", 'ok' => is_writable($path), 'required' => true, 'detail' => is_writable($path) ? 'OK' : 'not writable'];
        }
        $rows[] = ['label' => 'sodium (license keys)', 'ok' => extension_loaded('sodium'), 'required' => false, 'detail' => extension_loaded('sodium') ? 'OK' : 'optional'];

        return $rows;
    }

    public function requirementsMet(): bool
    {
        return collect($this->requirements())->where('required', true)->every(fn ($r) => $r['ok']);
    }

    /** @return array{ok:bool, error:?string} */
    public function testConnection(array $db): array
    {
        try {
            if ($db['driver'] === 'sqlite') {
                File::ensureDirectoryExists(dirname($db['database']));
                touch($db['database']);
                new PDO('sqlite:'.$db['database']);

                return ['ok' => true, 'error' => null];
            }

            $opts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5];
            $server = new PDO("mysql:host={$db['host']};port={$db['port']}", $db['username'], $db['password'], $opts);
            $name = str_replace('`', '', $db['database']);
            try {
                $server->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            } catch (\Throwable) {
                // no CREATE privilege (typical on shared hosting): the database may already exist
            }
            new PDO("mysql:host={$db['host']};port={$db['port']};dbname={$name}", $db['username'], $db['password'], $opts);

            return ['ok' => true, 'error' => null];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /** Save the connection to .env, switch this request over to it and create the tables. */
    public function apply(array $db, string $appUrl): void
    {
        $values = [
            'APP_URL' => $appUrl, 'DB_CONNECTION' => $db['driver'],
            'SESSION_DRIVER' => 'file', 'CACHE_STORE' => 'file', 'QUEUE_CONNECTION' => 'sync',
        ] + ($db['driver'] === 'sqlite'
            ? ['DB_DATABASE' => $db['database']]
            : ['DB_HOST' => $db['host'], 'DB_PORT' => (string) $db['port'], 'DB_DATABASE' => $db['database'], 'DB_USERNAME' => $db['username'], 'DB_PASSWORD' => $db['password']]);

        EnvWriter::set(static::envPath(), $values);

        $name = $db['driver'];
        config(['database.default' => $name] + ($name === 'sqlite'
            ? ['database.connections.sqlite.database' => $db['database']]
            : collect(['host' => $db['host'], 'port' => $db['port'], 'database' => $db['database'], 'username' => $db['username'], 'password' => $db['password']])->mapWithKeys(fn ($v, $k) => ["database.connections.mysql.{$k}" => $v])->all()));
        DB::purge($name);
        DB::setDefaultConnection($name);

        if (Artisan::call('migrate', ['--force' => true]) !== 0) {
            throw new RuntimeException('The database tables could not be created: '.trim(Artisan::output()));
        }
    }
}
