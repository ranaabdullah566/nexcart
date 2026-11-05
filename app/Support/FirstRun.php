<?php

namespace App\Support;

/**
 * Makes a fresh upload work without a terminal: creates .env from .env.example and an APP_KEY if they are missing.
 * Called from bootstrap/app.php, only for web requests.
 */
class FirstRun
{
    public static function ensureEnvironment(string $base): void
    {
        $env = $base.'/.env';
        if (! is_file($env) && is_file($env.'.example') && is_writable($base)) {
            @copy($env.'.example', $env);
        }
        if (! is_file($env) || ! is_writable($env)) {
            return;
        }

        $content = (string) file_get_contents($env);
        if (preg_match('/^APP_KEY=\s*$/m', $content)) {
            $key = 'base64:'.base64_encode(random_bytes(32));
            file_put_contents($env, preg_replace('/^APP_KEY=\s*$/m', 'APP_KEY='.$key, $content, 1), LOCK_EX);
            $_ENV['APP_KEY'] = $_SERVER['APP_KEY'] = $key;
            putenv('APP_KEY='.$key);
        }
    }
}
