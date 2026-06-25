<?php

declare(strict_types=1);

require_once __DIR__ . '/app.php';

function sucursalNodeMap(): array
{
    return [
        1 => [
            'key' => 'norte',
            'name' => 'Sucursal Norte',
            'host_env' => 'DB_NORTE_HOST',
            'port_env' => 'DB_NORTE_PORT',
            'db_env' => 'DB_NORTE_NAME',
            'user_env' => 'DB_NORTE_USER',
            'pass_env' => 'DB_NORTE_PASS',
            'host' => '127.0.0.1',
            'port' => '3306',
            'db' => 'libre_mercado_norte',
        ],
        2 => [
            'key' => 'centro',
            'name' => 'Sucursal Centro',
            'host_env' => 'DB_CENTRO_HOST',
            'port_env' => 'DB_CENTRO_PORT',
            'db_env' => 'DB_CENTRO_NAME',
            'user_env' => 'DB_CENTRO_USER',
            'pass_env' => 'DB_CENTRO_PASS',
            'host' => '127.0.0.1',
            'port' => '3306',
            'db' => 'libre_mercado_centro',
        ],
        3 => [
            'key' => 'sur',
            'name' => 'Sucursal Sur',
            'host_env' => 'DB_SUR_HOST',
            'port_env' => 'DB_SUR_PORT',
            'db_env' => 'DB_SUR_NAME',
            'user_env' => 'DB_SUR_USER',
            'pass_env' => 'DB_SUR_PASS',
            'host' => '127.0.0.1',
            'port' => '3306',
            'db' => 'libre_mercado_sur',
        ],
    ];
}

function sucursalConfigById(int $sucursalId): array
{
    $map = sucursalNodeMap();

    if (!isset($map[$sucursalId])) {
        throw new InvalidArgumentException('Sucursal no configurada para conexion distribuida.');
    }

    return $map[$sucursalId];
}

function sucursalConfigByKey(string $nodeKey): array
{
    foreach (sucursalNodeMap() as $config) {
        if ($config['key'] === $nodeKey) {
            return $config;
        }
    }

    throw new InvalidArgumentException('Nodo de sucursal desconocido.');
}

function dbSucursalById(int $sucursalId): PDO
{
    $config = sucursalConfigById($sucursalId);
    return dbSucursalByKey($config['key']);
}

function dbSucursalByKey(string $nodeKey): PDO
{
    static $connections = [];

    if (isset($connections[$nodeKey]) && $connections[$nodeKey] instanceof PDO) {
        return $connections[$nodeKey];
    }

    $config = sucursalConfigByKey($nodeKey);
    $host = envValue($config['host_env'], $config['host']);
    $port = envValue($config['port_env'], $config['port']);
    $db = envValue($config['db_env'], $config['db']);
    $user = envValue($config['user_env'], envValue('DB_USER', 'root'));
    $pass = envValue($config['pass_env'], envValue('DB_PASS', ''));

    $connections[$nodeKey] = createPdoWithRetry(
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

    return $connections[$nodeKey];
}

function allSucursalConnections(): array
{
    $connections = [];

    foreach (sucursalNodeMap() as $sucursalId => $config) {
        $connections[] = [
            'id_sucursal' => $sucursalId,
            'node_key' => $config['key'],
            'nombre' => $config['name'],
            'pdo' => dbSucursalByKey($config['key']),
        ];
    }

    return $connections;
}

function distributedId(string $nodeKey, int $localId): string
{
    return $nodeKey . ':' . $localId;
}

function parseDistributedId(string|int $id): array
{
    $value = (string) $id;

    if (str_contains($value, ':')) {
        [$nodeKey, $localId] = explode(':', $value, 2);
        return [$nodeKey, (int) $localId];
    }

    foreach (allSucursalConnections() as $connection) {
        $stmt = $connection['pdo']->prepare('SELECT 1');
        $stmt->execute();
        return [$connection['node_key'], (int) $value];
    }

    throw new InvalidArgumentException('Identificador distribuido invalido.');
}
