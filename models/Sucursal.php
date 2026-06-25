<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db_productos.php';

class Sucursal
{
    private PDO $db;

    public function __construct()
    {
        $this->db = dbProductos();
    }

    public function all(): array
    {
        return $this->db->query('SELECT * FROM sucursales ORDER BY id_sucursal DESC')->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM sucursales WHERE id_sucursal = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): void
    {
        $stmt = $this->db->prepare('INSERT INTO sucursales (nombre) VALUES (:nombre)');
        $stmt->execute(['nombre' => trim($data['nombre'])]);
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare('UPDATE sucursales SET nombre = :nombre WHERE id_sucursal = :id');
        $stmt->execute(['id' => $id, 'nombre' => trim($data['nombre'])]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM sucursales WHERE id_sucursal = :id');
        $stmt->execute(['id' => $id]);
    }
}
