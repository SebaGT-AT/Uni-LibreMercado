<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db_central.php';
require_once __DIR__ . '/../config/db_sucursales.php';

class Nodo
{
    private PDO $dbCentral;

    public function __construct()
    {
        $this->dbCentral = dbCentral();
    }

    public function all(): array
    {
        $stmt = $this->dbCentral->query(
            'SELECT ns.*, s.nombre
             FROM node_status ns
             INNER JOIN sucursales s ON s.id_sucursal = ns.id_sucursal
             ORDER BY ns.id_sucursal ASC'
        );

        return $stmt->fetchAll();
    }

    public function find(int $sucursalId): ?array
    {
        $stmt = $this->dbCentral->prepare(
            'SELECT ns.*, s.nombre
             FROM node_status ns
             INNER JOIN sucursales s ON s.id_sucursal = ns.id_sucursal
             WHERE ns.id_sucursal = :id'
        );
        $stmt->execute(['id' => $sucursalId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function isOnline(int $sucursalId): bool
    {
        $node = $this->find($sucursalId);
        return $node ? (int) $node['online'] === 1 : false;
    }

    public function isAvailableForOperations(int $sucursalId): bool
    {
        return $this->isOnline($sucursalId) && $this->actualConnectivity($sucursalId);
    }

    public function setStatus(int $sucursalId, bool $online, string $message = ''): void
    {
        $stmt = $this->dbCentral->prepare('CALL sp_set_node_status(:id_sucursal, :online, :mensaje)');
        $stmt->execute([
            'id_sucursal' => $sucursalId,
            'online' => $online ? 1 : 0,
            'mensaje' => $message,
        ]);
        $stmt->closeCursor();
    }

    public function actualConnectivity(int $sucursalId): bool
    {
        $pdo = tryDbSucursalById($sucursalId);

        if (!$pdo instanceof PDO) {
            return false;
        }

        try {
            $stmt = $pdo->query('SELECT 1');
            return (bool) $stmt->fetchColumn();
        } catch (Throwable) {
            return false;
        }
    }

    public function statusSnapshot(int $sucursalId): ?array
    {
        $node = $this->find($sucursalId);

        if (!$node) {
            return null;
        }

        $reachable = $this->actualConnectivity($sucursalId);
        $logicalOnline = (int) $node['online'] === 1;

        $node['logical_online'] = $logicalOnline ? 1 : 0;
        $node['reachable'] = $reachable ? 1 : 0;
        $node['available'] = ($logicalOnline && $reachable) ? 1 : 0;

        if ($logicalOnline && !$reachable) {
            $node['effective_status'] = 'DEGRADED';
            $node['effective_message'] = 'Estado logico ONLINE, pero el contenedor o la conexion real no responde.';
        } elseif (!$logicalOnline) {
            $node['effective_status'] = 'OFFLINE';
            $node['effective_message'] = $node['status_message'] ?: 'Nodo marcado manualmente como OFFLINE.';
        } else {
            $node['effective_status'] = 'ONLINE';
            $node['effective_message'] = $node['status_message'] ?: 'Nodo disponible para operar.';
        }

        return $node;
    }
}
