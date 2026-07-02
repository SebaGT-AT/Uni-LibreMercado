<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db_central.php';
require_once __DIR__ . '/../config/db_sucursales.php';

class Carrito
{
    private PDO $dbCentral;

    public function __construct()
    {
        $this->dbCentral = dbCentral();
    }

    public function all(): array
    {
        return $this->collectCarritos();
    }

    public function allByCliente(int $clienteId): array
    {
        return $this->collectCarritos($clienteId);
    }

    public function find(string|int $id): ?array
    {
        [$nodeKey, $localId] = $this->resolveDistributedId($id);

        if ($nodeKey === null) {
            return null;
        }

        $pdo = tryDbSucursalByKey($nodeKey);

        if (!$pdo instanceof PDO) {
            return null;
        }

        $stmt = $pdo->prepare('SELECT * FROM carrito WHERE id_carrito = :id');
        $stmt->execute(['id' => $localId]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        $detailStmt = $pdo->prepare('SELECT * FROM detalle_carrito WHERE id_carrito = :id');
        $detailStmt->execute(['id' => $localId]);
        $row['detalle'] = $detailStmt->fetchAll();
        $row['global_id'] = distributedId($nodeKey, (int) $row['id_carrito']);
        return $row;
    }

    public function findForCliente(string|int $id, int $clienteId): ?array
    {
        $carrito = $this->find($id);

        if (!$carrito || (int) $carrito['id_cliente'] !== $clienteId) {
            return null;
        }

        return $carrito;
    }

    public function create(array $data): void
    {
        $this->persist(null, $data);
    }

    public function update(string|int $id, array $data): void
    {
        $this->persist($id, $data);
    }

    public function delete(string|int $id): void
    {
        [$nodeKey, $localId] = $this->resolveDistributedId($id);

        if ($nodeKey === null) {
            return;
        }

        $pdo = dbSucursalByKey($nodeKey);
        $stmt = $pdo->prepare('DELETE FROM carrito WHERE id_carrito = :id');
        $stmt->execute(['id' => $localId]);
    }

    public function deleteForCliente(string|int $id, int $clienteId): void
    {
        $carrito = $this->findForCliente($id, $clienteId);
        if (!$carrito) {
            return;
        }

        $stmt = dbSucursalById((int) $carrito['id_sucursal'])->prepare('DELETE FROM carrito WHERE id_carrito = :id AND id_cliente = :id_cliente');
        $stmt->execute([
            'id' => (int) $carrito['id_carrito'],
            'id_cliente' => $clienteId,
        ]);
    }

    private function persist(string|int|null $id, array $data): void
    {
        $sucursalId = (int) $data['id_sucursal'];
        $pdo = dbSucursalById($sucursalId);
        $pdo->beginTransaction();

        try {
            if ($id !== null) {
                $current = $this->find($id);
                if (!$current) {
                    throw new RuntimeException('Carrito no encontrado.');
                }

                if ((int) $current['id_sucursal'] !== $sucursalId) {
                    $oldPdo = dbSucursalById((int) $current['id_sucursal']);
                    $oldPdo->beginTransaction();
                    try {
                        $delete = $oldPdo->prepare('DELETE FROM carrito WHERE id_carrito = :id');
                        $delete->execute(['id' => (int) $current['id_carrito']]);
                        $oldPdo->commit();
                    } catch (Throwable $e) {
                        if ($oldPdo->inTransaction()) {
                            $oldPdo->rollBack();
                        }
                        throw $e;
                    }

                    $stmt = $pdo->prepare(
                        'INSERT INTO carrito (id_cliente, id_sucursal, fecha) VALUES (:cliente, :sucursal, :fecha)'
                    );
                    $stmt->execute([
                        'cliente' => (int) $data['id_cliente'],
                        'sucursal' => $sucursalId,
                        'fecha' => $data['fecha'],
                    ]);
                    $carritoId = (int) $pdo->lastInsertId();
                } else {
                    $stmt = $pdo->prepare(
                        'UPDATE carrito SET id_cliente = :cliente, id_sucursal = :sucursal, fecha = :fecha WHERE id_carrito = :id'
                    );
                    $stmt->execute([
                        'cliente' => (int) $data['id_cliente'],
                        'sucursal' => $sucursalId,
                        'fecha' => $data['fecha'],
                        'id' => (int) $current['id_carrito'],
                    ]);

                    $delete = $pdo->prepare('DELETE FROM detalle_carrito WHERE id_carrito = :id');
                    $delete->execute(['id' => (int) $current['id_carrito']]);
                    $carritoId = (int) $current['id_carrito'];
                }
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO carrito (id_cliente, id_sucursal, fecha) VALUES (:cliente, :sucursal, :fecha)'
                );
                $stmt->execute([
                    'cliente' => (int) $data['id_cliente'],
                    'sucursal' => $sucursalId,
                    'fecha' => $data['fecha'],
                ]);
                $carritoId = (int) $pdo->lastInsertId();
            }

            $detail = $pdo->prepare(
                'INSERT INTO detalle_carrito (id_carrito, id_producto, cantidad) VALUES (:carrito, :producto, :cantidad)'
            );
            foreach ($data['items'] as $item) {
                $detail->execute([
                    'carrito' => $carritoId,
                    'producto' => (int) $item['id_producto'],
                    'cantidad' => (int) $item['cantidad'],
                ]);
            }

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    private function collectCarritos(?int $clienteId = null): array
    {
        $clientes = $this->indexedClientes();
        $sucursales = $this->indexedSucursales();
        $rows = [];

        foreach (allSucursalConnections() as $connection) {
            if ($clienteId !== null) {
                $stmt = $connection['pdo']->prepare('SELECT * FROM carrito WHERE id_cliente = :id_cliente ORDER BY id_carrito DESC');
                $stmt->execute(['id_cliente' => $clienteId]);
                $items = $stmt->fetchAll();
            } else {
                $items = $connection['pdo']->query('SELECT * FROM carrito ORDER BY id_carrito DESC')->fetchAll();
            }

            foreach ($items as $item) {
                $rows[] = [
                    'global_id' => distributedId($connection['node_key'], (int) $item['id_carrito']),
                    'id_carrito' => (int) $item['id_carrito'],
                    'id_cliente' => (int) $item['id_cliente'],
                    'id_sucursal' => (int) $item['id_sucursal'],
                    'nombre' => $clientes[(int) $item['id_cliente']]['nombre'] ?? 'Cliente desconocido',
                    'sucursal' => $sucursales[(int) $item['id_sucursal']]['nombre'] ?? $connection['nombre'],
                    'fecha' => $item['fecha'],
                ];
            }
        }

        usort(
            $rows,
            static fn(array $a, array $b): int => strcmp((string) $b['fecha'], (string) $a['fecha'])
        );

        return $rows;
    }

    private function indexedClientes(): array
    {
        $stmt = $this->dbCentral->query('SELECT id_cliente, nombre FROM clientes');
        $items = [];
        foreach ($stmt->fetchAll() as $cliente) {
            $items[(int) $cliente['id_cliente']] = $cliente;
        }
        return $items;
    }

    private function indexedSucursales(): array
    {
        $stmt = $this->dbCentral->query('SELECT id_sucursal, nombre FROM sucursales');
        $items = [];
        foreach ($stmt->fetchAll() as $sucursal) {
            $items[(int) $sucursal['id_sucursal']] = $sucursal;
        }
        return $items;
    }

    private function resolveDistributedId(string|int $id): array
    {
        $value = (string) $id;

        if (str_contains($value, ':')) {
            return parseDistributedId($value);
        }

        foreach (allSucursalConnections() as $connection) {
            $stmt = $connection['pdo']->prepare('SELECT id_carrito FROM carrito WHERE id_carrito = :id');
            $stmt->execute(['id' => (int) $value]);
            if ($stmt->fetchColumn()) {
                return [$connection['node_key'], (int) $value];
            }
        }

        return [null, 0];
    }
}
