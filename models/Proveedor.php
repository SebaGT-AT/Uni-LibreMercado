<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db_ventas.php';

class Proveedor
{
    private PDO $db;

    public function __construct()
    {
        $this->db = dbVentas();
    }

    public function all(): array
    {
        return $this->db->query('SELECT * FROM proveedores ORDER BY id_proveedor DESC')->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM proveedores WHERE id_proveedor = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): void
    {
        $stmt = $this->db->prepare('INSERT INTO proveedores (nombre, telefono) VALUES (:nombre, :telefono)');
        $stmt->execute([
            'nombre' => trim($data['nombre']),
            'telefono' => trim($data['telefono']),
        ]);
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare('UPDATE proveedores SET nombre = :nombre, telefono = :telefono WHERE id_proveedor = :id');
        $stmt->execute([
            'id' => $id,
            'nombre' => trim($data['nombre']),
            'telefono' => trim($data['telefono']),
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM proveedores WHERE id_proveedor = :id');
        $stmt->execute(['id' => $id]);
    }
}
