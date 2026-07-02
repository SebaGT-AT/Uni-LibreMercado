<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db_central.php';
require_once __DIR__ . '/../config/db_sucursales.php';

class Stock
{
    private PDO $dbCentral;

    public function __construct()
    {
        $this->dbCentral = dbCentral();
    }

    public function all(): array
    {
        $productos = $this->indexedProductos();
        $sucursales = $this->indexedSucursales();
        $rows = [];

        foreach (allSucursalConnections() as $connection) {
            $items = $connection['pdo']->query('SELECT * FROM stock ORDER BY id_stock DESC')->fetchAll();

            foreach ($items as $item) {
                $rows[] = [
                    'global_id' => distributedId($connection['node_key'], (int) $item['id_stock']),
                    'id_stock' => (int) $item['id_stock'],
                    'id_producto' => (int) $item['id_producto'],
                    'id_sucursal' => (int) $item['id_sucursal'],
                    'cantidad' => (int) $item['cantidad'],
                    'producto' => $productos[(int) $item['id_producto']]['producto'] ?? 'Producto desconocido',
                    'sucursal' => $sucursales[(int) $item['id_sucursal']]['nombre'] ?? $connection['nombre'],
                ];
            }
        }

        usort(
            $rows,
            static fn(array $a, array $b): int => strcmp($b['global_id'], $a['global_id'])
        );

        return $rows;
    }

    public function find(string|int $id): ?array
    {
        [$nodeKey, $localId] = $this->resolveDistributedId($id, 'stock', 'id_stock');

        if ($nodeKey === null) {
            return null;
        }

        $pdo = tryDbSucursalByKey($nodeKey);

        if (!$pdo instanceof PDO) {
            return null;
        }

        $stmt = $pdo->prepare('SELECT * FROM stock WHERE id_stock = :id');
        $stmt->execute(['id' => $localId]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $row['global_id'] = distributedId($nodeKey, (int) $row['id_stock']);
        return $row;
    }

    public function getQuantity(int $productoId, int $sucursalId): int
    {
        $stmt = dbSucursalById($sucursalId)->prepare(
            'SELECT cantidad FROM stock WHERE id_producto = :producto AND id_sucursal = :sucursal'
        );
        $stmt->execute([
            'producto' => $productoId,
            'sucursal' => $sucursalId,
        ]);
        $row = $stmt->fetch();

        return $row ? (int) $row['cantidad'] : 0;
    }

    public function availabilityBySucursal(int $sucursalId): array
    {
        $pdo = tryDbSucursalById($sucursalId);

        if (!$pdo instanceof PDO) {
            $config = sucursalConfigById($sucursalId);
            throw new RuntimeException('La sucursal ' . $config['name'] . ' no esta disponible en este momento.');
        }

        $productos = $this->indexedProductos();
        $stmt = $pdo->prepare(
            'SELECT id_producto, id_sucursal, cantidad
             FROM stock
             WHERE id_sucursal = :sucursal
             ORDER BY id_producto ASC'
        );
        $stmt->execute(['sucursal' => $sucursalId]);

        $rows = [];
        foreach ($stmt->fetchAll() as $item) {
            $productoId = (int) $item['id_producto'];
            $rows[] = [
                'id_producto' => $productoId,
                'id_sucursal' => (int) $item['id_sucursal'],
                'producto' => $productos[$productoId]['producto'] ?? 'Producto desconocido',
                'precio' => isset($productos[$productoId]['precio']) ? (float) $productos[$productoId]['precio'] : 0.0,
                'cantidad' => (int) $item['cantidad'],
            ];
        }

        return $rows;
    }

    public function create(array $data): void
    {
        $pdo = dbSucursalById((int) $data['id_sucursal']);
        $stmt = $pdo->prepare(
            'INSERT INTO stock (id_producto, id_sucursal, cantidad) VALUES (:id_producto, :id_sucursal, :cantidad)'
        );
        $stmt->execute([
            'id_producto' => (int) $data['id_producto'],
            'id_sucursal' => (int) $data['id_sucursal'],
            'cantidad' => (int) $data['cantidad'],
        ]);
    }

    public function update(string|int $id, array $data): void
    {
        $current = $this->find($id);
        if (!$current) {
            throw new RuntimeException('Registro de stock no encontrado.');
        }

        $targetSucursalId = (int) $data['id_sucursal'];
        $currentSucursalId = (int) $current['id_sucursal'];

        if ($targetSucursalId !== $currentSucursalId) {
            $this->delete((string) $current['global_id']);
            $this->create($data);
            return;
        }

        $pdo = dbSucursalById($targetSucursalId);
        $stmt = $pdo->prepare(
            'UPDATE stock SET id_producto = :id_producto, id_sucursal = :id_sucursal, cantidad = :cantidad WHERE id_stock = :id'
        );
        $stmt->execute([
            'id' => (int) $current['id_stock'],
            'id_producto' => (int) $data['id_producto'],
            'id_sucursal' => $targetSucursalId,
            'cantidad' => (int) $data['cantidad'],
        ]);
    }

    public function delete(string|int $id): void
    {
        [$nodeKey, $localId] = $this->resolveDistributedId($id, 'stock', 'id_stock');
        if ($nodeKey === null) {
            return;
        }

        $stmt = dbSucursalByKey($nodeKey)->prepare('DELETE FROM stock WHERE id_stock = :id');
        $stmt->execute(['id' => $localId]);
    }

    public function increment(int $productoId, int $sucursalId, int $cantidad): void
    {
        $stmt = dbSucursalById($sucursalId)->prepare(
            'INSERT INTO stock (id_producto, id_sucursal, cantidad)
             VALUES (:producto, :sucursal, :cantidad)
             ON DUPLICATE KEY UPDATE cantidad = cantidad + VALUES(cantidad)'
        );
        $stmt->execute([
            'producto' => $productoId,
            'sucursal' => $sucursalId,
            'cantidad' => $cantidad,
        ]);
    }

    public function decrement(int $productoId, int $sucursalId, int $cantidad): void
    {
        if ($this->getQuantity($productoId, $sucursalId) < $cantidad) {
            throw new RuntimeException('Stock insuficiente para completar la operacion.');
        }

        $stmt = dbSucursalById($sucursalId)->prepare(
            'UPDATE stock
             SET cantidad = cantidad - :cantidad
             WHERE id_producto = :producto AND id_sucursal = :sucursal'
        );
        $stmt->execute([
            'cantidad' => $cantidad,
            'producto' => $productoId,
            'sucursal' => $sucursalId,
        ]);
    }

    private function indexedProductos(): array
    {
        $stmt = $this->dbCentral->query('SELECT id_producto, producto, precio FROM productos');
        $items = [];

        foreach ($stmt->fetchAll() as $producto) {
            $items[(int) $producto['id_producto']] = $producto;
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

    private function resolveDistributedId(string|int $id, string $table, string $primaryKey): array
    {
        $value = (string) $id;

        if (str_contains($value, ':')) {
            return parseDistributedId($value);
        }

        foreach (allSucursalConnections() as $connection) {
            $stmt = $connection['pdo']->prepare("SELECT {$primaryKey} FROM {$table} WHERE {$primaryKey} = :id");
            $stmt->execute(['id' => (int) $value]);
            if ($stmt->fetchColumn()) {
                return [$connection['node_key'], (int) $value];
            }
        }

        return [null, 0];
    }
}
