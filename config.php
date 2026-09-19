<?php
declare(strict_types=1);

/**
 * Lightweight environment loader for local development.
 * Production environments should provide real environment variables.
 */
function loadEnvFile(string $path): void
{
    if (!is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = array_map('trim', explode('=', $line, 2));
        if ($key === '' || getenv($key) !== false) {
            continue;
        }

        if (
            strlen($value) >= 2 &&
            (($value[0] === '"' && $value[strlen($value) - 1] === '"') ||
             ($value[0] === "'" && $value[strlen($value) - 1] === "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
    }
}

function envValue(string $key, ?string $default = null): ?string
{
    $value = getenv($key);
    return ($value === false || $value === '') ? $default : $value;
}

loadEnvFile(__DIR__ . '/.env');

define('DB_HOST', envValue('DB_HOST', 'localhost'));
define('DB_PORT', (int) envValue('DB_PORT', '3306'));
define('DB_USER', envValue('DB_USER', 'root'));
define('DB_PASS', envValue('DB_PASS', ''));
define('DB_NAME', envValue('DB_NAME', 'coursematch_db'));

define('APP_URL', rtrim((string) envValue('APP_URL', 'http://localhost/CourseRecommender'), '/'));
define('APP_ENV', envValue('APP_ENV', 'local'));

define('MAIL_HOST', envValue('MAIL_HOST', 'smtp.gmail.com'));
define('MAIL_PORT', (int) envValue('MAIL_PORT', '587'));
define('MAIL_USERNAME', envValue('MAIL_USERNAME', ''));
define('MAIL_PASSWORD', envValue('MAIL_PASSWORD', ''));
define('MAIL_FROM_ADDRESS', envValue('MAIL_FROM_ADDRESS', MAIL_USERNAME));
define('MAIL_FROM_NAME', envValue('MAIL_FROM_NAME', 'CourseMatch'));
