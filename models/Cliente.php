<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db_clientes.php';

class Cliente
{
    private PDO $db;

    public function __construct()
    {
        $this->db = dbClientes();
    }

    public function all(): array
    {
        $sql = 'SELECT c.*, u.username, u.rol
                FROM clientes c
                INNER JOIN usuarios u ON u.id_usuario = c.id_usuario
                ORDER BY c.id_cliente DESC';
        return $this->db->query($sql)->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM clientes WHERE id_cliente = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByUsuarioId(int $usuarioId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM clientes WHERE id_usuario = :id_usuario');
        $stmt->execute(['id_usuario' => $usuarioId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): void
    {
        $stmt = $this->db->prepare('INSERT INTO clientes (nombre, telefono, id_usuario) VALUES (:nombre, :telefono, :id_usuario)');
        $stmt->execute([
            'nombre' => trim($data['nombre']),
            'telefono' => trim($data['telefono']),
            'id_usuario' => (int) $data['id_usuario'],
        ]);
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare('UPDATE clientes SET nombre = :nombre, telefono = :telefono, id_usuario = :id_usuario WHERE id_cliente = :id');
        $stmt->execute([
            'id' => $id,
            'nombre' => trim($data['nombre']),
            'telefono' => trim($data['telefono']),
            'id_usuario' => (int) $data['id_usuario'],
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM clientes WHERE id_cliente = :id');
        $stmt->execute(['id' => $id]);
    }
}
