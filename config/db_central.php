<?php

declare(strict_types=1);

require_once __DIR__ . '/app.php';

function dbCentral(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = envValue('DB_CENTRAL_HOST', envValue('DB_HOST', '127.0.0.1'));
    $port = envValue('DB_CENTRAL_PORT', envValue('DB_PORT', '3306'));
    $db = envValue('DB_CENTRAL_NAME', 'libre_mercado_central');
    $user = envValue('DB_CENTRAL_USER', envValue('DB_USER', 'root'));
    $pass = envValue('DB_CENTRAL_PASS', envValue('DB_PASS', ''));

    $pdo = createPdoWithRetry(
        "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ],
        10,
        1500
    );

    return $pdo;
}
