<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db_central.php';
require_once __DIR__ . '/../config/db_sucursales.php';
require_once __DIR__ . '/Stock.php';

class Compra
{
    private PDO $dbCentral;
    private Stock $stockModel;

    public function __construct()
    {
        $this->dbCentral = dbCentral();
        $this->stockModel = new Stock();
    }

    public function all(): array
    {
        $proveedores = $this->indexedProviders();
        $sucursales = $this->indexedSucursales();
        $rows = [];

        foreach (allSucursalConnections() as $connection) {
            $items = $connection['pdo']->query('SELECT * FROM compras ORDER BY id_compra DESC')->fetchAll();

            foreach ($items as $item) {
                $rows[] = [
                    'global_id' => distributedId($connection['node_key'], (int) $item['id_compra']),
                    'id_compra' => (int) $item['id_compra'],
                    'id_proveedor' => (int) $item['id_proveedor'],
                    'id_sucursal' => (int) $item['id_sucursal'],
                    'fecha' => $item['fecha'],
                    'proveedor' => $proveedores[(int) $item['id_proveedor']]['nombre'] ?? 'Proveedor desconocido',
                    'sucursal' => $sucursales[(int) $item['id_sucursal']]['nombre'] ?? $connection['nombre'],
                ];
            }
        }

        usort(
            $rows,
            static fn(array $a, array $b): int => strcmp((string) $b['fecha'], (string) $a['fecha'])
        );

        return $rows;
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

        $stmt = $pdo->prepare('SELECT * FROM compras WHERE id_compra = :id');
        $stmt->execute(['id' => $localId]);
        $compra = $stmt->fetch();

        if (!$compra) {
            return null;
        }

        $detailStmt = $pdo->prepare('SELECT * FROM detalle_compras WHERE id_compra = :id');
        $detailStmt->execute(['id' => $localId]);
        $compra['detalle'] = $detailStmt->fetchAll();
        $compra['global_id'] = distributedId($nodeKey, (int) $compra['id_compra']);
        return $compra;
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
        $compra = $this->find($id);
        if (!$compra) {
            throw new RuntimeException('Compra no encontrada o nodo no disponible.');
        }

        $pdo = dbSucursalById((int) $compra['id_sucursal']);
        $pdo->beginTransaction();

        try {
            foreach ($compra['detalle'] as $item) {
                $this->stockModel->decrement((int) $item['id_producto'], (int) $compra['id_sucursal'], (int) $item['cantidad']);
            }

            $stmt = $pdo->prepare('DELETE FROM compras WHERE id_compra = :id');
            $stmt->execute(['id' => (int) $compra['id_compra']]);

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    private function persist(string|int|null $id, array $data): void
    {
        $items = $data['items'];
        $sucursalId = (int) $data['id_sucursal'];
        $pdo = dbSucursalById($sucursalId);
        $pdo->beginTransaction();

        try {
            if ($id !== null) {
                $previous = $this->find($id);
                if (!$previous) {
                    throw new RuntimeException('Compra no encontrada o nodo no disponible.');
                }

                foreach ($previous['detalle'] as $item) {
                    $this->stockModel->decrement((int) $item['id_producto'], (int) $previous['id_sucursal'], (int) $item['cantidad']);
                }

                if ((int) $previous['id_sucursal'] !== $sucursalId) {
                    $oldPdo = dbSucursalById((int) $previous['id_sucursal']);
                    $oldPdo->beginTransaction();
                    try {
                        $delete = $oldPdo->prepare('DELETE FROM compras WHERE id_compra = :id');
                        $delete->execute(['id' => (int) $previous['id_compra']]);
                        $oldPdo->commit();
                    } catch (Throwable $e) {
                        if ($oldPdo->inTransaction()) {
                            $oldPdo->rollBack();
                        }
                        throw $e;
                    }

                    $insert = $pdo->prepare(
                        'INSERT INTO compras (id_proveedor, id_sucursal, fecha) VALUES (:proveedor, :sucursal, :fecha)'
                    );
                    $insert->execute([
                        'proveedor' => (int) $data['id_proveedor'],
                        'sucursal' => $sucursalId,
                        'fecha' => $data['fecha'],
                    ]);
                    $compraId = (int) $pdo->lastInsertId();
                } else {
                    $update = $pdo->prepare(
                        'UPDATE compras SET id_proveedor = :proveedor, id_sucursal = :sucursal, fecha = :fecha WHERE id_compra = :id'
                    );
                    $update->execute([
                        'proveedor' => (int) $data['id_proveedor'],
                        'sucursal' => $sucursalId,
                        'fecha' => $data['fecha'],
                        'id' => (int) $previous['id_compra'],
                    ]);

                    $delete = $pdo->prepare('DELETE FROM detalle_compras WHERE id_compra = :id');
                    $delete->execute(['id' => (int) $previous['id_compra']]);
                    $compraId = (int) $previous['id_compra'];
                }
            } else {
                $insert = $pdo->prepare(
                    'INSERT INTO compras (id_proveedor, id_sucursal, fecha) VALUES (:proveedor, :sucursal, :fecha)'
                );
                $insert->execute([
                    'proveedor' => (int) $data['id_proveedor'],
                    'sucursal' => $sucursalId,
                    'fecha' => $data['fecha'],
                ]);
                $compraId = (int) $pdo->lastInsertId();
            }

            $detail = $pdo->prepare(
                'INSERT INTO detalle_compras (id_compra, id_producto, cantidad, costo) VALUES (:compra, :producto, :cantidad, :costo)'
            );
            foreach ($items as $item) {
                $detail->execute([
                    'compra' => $compraId,
                    'producto' => (int) $item['id_producto'],
                    'cantidad' => (int) $item['cantidad'],
                    'costo' => (float) $item['precio'],
                ]);
                $this->stockModel->increment((int) $item['id_producto'], $sucursalId, (int) $item['cantidad']);
            }

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    private function indexedProviders(): array
    {
        $stmt = $this->dbCentral->query('SELECT id_proveedor, nombre FROM proveedores');
        $items = [];
        foreach ($stmt->fetchAll() as $provider) {
            $items[(int) $provider['id_proveedor']] = $provider;
        }
        return $items;
    }

    private function indexedSucursales(): array
    {
        $stmt = $this->dbCentral->query('SELECT id_sucursal, nombre FROM sucursales');
        $items = [];
        foreach ($stmt->fetchAll() as $branch) {
            $items[(int) $branch['id_sucursal']] = $branch;
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
            $stmt = $connection['pdo']->prepare('SELECT id_compra FROM compras WHERE id_compra = :id');
            $stmt->execute(['id' => (int) $value]);
            if ($stmt->fetchColumn()) {
                return [$connection['node_key'], (int) $value];
            }
        }

        return [null, 0];
    }
}
