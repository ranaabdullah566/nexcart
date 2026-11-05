<?php

namespace App\Support;

/** Edits KEY=value lines in an .env file without disturbing the rest of it. */
class EnvWriter
{
    public static function set(string $path, array $values): void
    {
        $content = is_file($path) ? (string) file_get_contents($path) : '';

        foreach ($values as $key => $value) {
            if (! preg_match('/^[A-Z][A-Z0-9_]*$/', $key)) {
                throw new \InvalidArgumentException("Bad environment key: {$key}");
            }
            $line = $key.'='.static::format((string) $value);
            $pattern = '/^'.preg_quote($key, '/').'=.*$/m';

            $content = preg_match($pattern, $content)
                ? preg_replace($pattern, str_replace(['\\', '$'], ['\\\\', '\\$'], $line), $content)
                : rtrim($content, "\n")."\n".$line."\n";
        }

        file_put_contents($path, $content, LOCK_EX);
    }

    /** Quote only when needed, escaping the characters dotenv treats specially. */
    public static function format(string $value): string
    {
        if ($value === '' || preg_match('/^[A-Za-z0-9_.:\/\-@+,]+$/', $value)) {
            return $value;
        }

        return '"'.str_replace(['\\', '"', '$', "\n", "\r"], ['\\\\', '\\"', '\\$', '\\n', ''], $value).'"';
    }
}
