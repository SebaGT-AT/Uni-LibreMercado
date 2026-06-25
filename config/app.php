<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('APP_NAME', 'Libre Mercado');
define('BASE_URL', rtrim((string) envValue('BASE_URL', ''), '/'));

date_default_timezone_set('America/Santiago');

function envValue(string $key, ?string $default = null): ?string
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    return $value === false ? $default : ($value ?: $default);
}

function createPdoWithRetry(string $dsn, string $user, string $pass, array $options = [], int $attempts = 10, int $delayMs = 1500): PDO
{
    $lastException = null;

    for ($i = 1; $i <= $attempts; $i++) {
        try {
            return new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            $lastException = $e;

            if ($i < $attempts) {
                usleep($delayMs * 1000);
            }
        }
    }

    throw $lastException ?? new PDOException('No fue posible establecer la conexion con la base de datos.');
}
