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
        try {
            $pdo = dbSucursalById($sucursalId);
            $stmt = $pdo->query('SELECT 1');
            return (bool) $stmt->fetchColumn();
        } catch (Throwable) {
            return false;
        }
    }
}
