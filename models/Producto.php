<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db_productos.php';

class Producto
{
    private PDO $db;

    public function __construct()
    {
        $this->db = dbProductos();
    }

    public function all(): array
    {
        return $this->db->query('SELECT * FROM productos ORDER BY id_producto DESC')->fetchAll();
    }

    public function active(): array
    {
        return $this->db->query('SELECT * FROM productos WHERE activo = 1 ORDER BY producto')->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM productos WHERE id_producto = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): void
    {
        $stmt = $this->db->prepare('INSERT INTO productos (producto, precio, descripcion, activo) VALUES (:producto, :precio, :descripcion, 1)');
        $stmt->execute([
            'producto' => trim($data['producto']),
            'precio' => (float) $data['precio'],
            'descripcion' => trim((string) ($data['descripcion'] ?? '')),
        ]);
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare('UPDATE productos SET producto = :producto, precio = :precio, descripcion = :descripcion WHERE id_producto = :id');
        $stmt->execute([
            'id' => $id,
            'producto' => trim($data['producto']),
            'precio' => (float) $data['precio'],
            'descripcion' => trim((string) ($data['descripcion'] ?? '')),
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE productos SET activo = 0 WHERE id_producto = :id');
        $stmt->execute(['id' => $id]);
    }
}
