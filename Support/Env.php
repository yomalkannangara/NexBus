<?php
namespace App\Support;

class Env
{
    public static function load(string $path = null): void
    {
        // if only folder passed → append ".env"
        if ($path === null) {
            $path = dirname(__DIR__, 1) . '/.env';
        } elseif (is_dir($path)) {
            $path = rtrim($path, '/\\') . '/.env';
        }

        if (!is_file($path)) {
            error_log("⚠️ Env file not found at $path");
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            [$key, $value] = array_map('trim', explode('=', $line, 2));
            $value = trim($value, "\"'");

            // Expand variables like ${DB_HOST}
            $value = preg_replace_callback(
                '/\$\{(\w+)\}/',
                fn($m) => $_ENV[$m[1]] ?? getenv($m[1]) ?? '',
                $value
            );

            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }

    public static function get(string $key, $default = null)
    {
        return $_ENV[$key] ?? getenv($key) ?: $default;
    }
}
