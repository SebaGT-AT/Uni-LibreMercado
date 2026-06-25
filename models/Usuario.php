<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db_clientes.php';

class Usuario
{
    private PDO $db;

    public function __construct()
    {
        $this->db = dbClientes();
    }

    public function all(): array
    {
        return $this->db->query('SELECT * FROM usuarios ORDER BY id_usuario DESC')->fetchAll();
    }

    public function activeClients(?int $includeUserId = null): array
    {
        $sql = "SELECT u.*
                FROM usuarios u
                LEFT JOIN clientes c ON c.id_usuario = u.id_usuario
                WHERE u.activo = 1
                  AND u.rol = 'CLIENTE'
                  AND (c.id_usuario IS NULL";

        $params = [];
        if ($includeUserId !== null) {
            $sql .= ' OR u.id_usuario = :includeUserId';
            $params['includeUserId'] = $includeUserId;
        }

        $sql .= ') ORDER BY u.username';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM usuarios WHERE id_usuario = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM usuarios WHERE username = :username AND activo = 1');
        $stmt->execute(['username' => $username]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): void
    {
        $stmt = $this->db->prepare('INSERT INTO usuarios (username, password, rol, activo) VALUES (:username, :password, :rol, 1)');
        $stmt->execute([
            'username' => trim($data['username']),
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'rol' => $data['rol'],
        ]);
    }

    public function update(int $id, array $data): void
    {
        $fields = 'username = :username, rol = :rol';
        $params = [
            'id' => $id,
            'username' => trim($data['username']),
            'rol' => $data['rol'],
        ];

        if (!empty($data['password'])) {
            $fields .= ', password = :password';
            $params['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $stmt = $this->db->prepare("UPDATE usuarios SET {$fields} WHERE id_usuario = :id");
        $stmt->execute($params);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE usuarios SET activo = 0 WHERE id_usuario = :id');
        $stmt->execute(['id' => $id]);
    }

    public function updatePassword(int $id, string $plainPassword): void
    {
        $stmt = $this->db->prepare('UPDATE usuarios SET password = :password WHERE id_usuario = :id');
        $stmt->execute([
            'id' => $id,
            'password' => password_hash($plainPassword, PASSWORD_DEFAULT),
        ]);
    }
}
