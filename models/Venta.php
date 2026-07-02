<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db_central.php';
require_once __DIR__ . '/../config/db_sucursales.php';
require_once __DIR__ . '/Stock.php';
require_once __DIR__ . '/Producto.php';
require_once __DIR__ . '/Nodo.php';

class Venta
{
    private PDO $dbCentral;
    private Stock $stockModel;
    private Producto $productoModel;
    private Nodo $nodoModel;

    public function __construct()
    {
        $this->dbCentral = dbCentral();
        $this->stockModel = new Stock();
        $this->productoModel = new Producto();
        $this->nodoModel = new Nodo();
    }

    public function all(): array
    {
        $sql = 'SELECT v.*, c.nombre AS cliente, s.nombre AS sucursal
                FROM ventas v
                INNER JOIN clientes c ON c.id_cliente = v.id_cliente
                INNER JOIN sucursales s ON s.id_sucursal = v.id_sucursal
                ORDER BY v.id_venta DESC';
        return $this->dbCentral->query($sql)->fetchAll();
    }

    public function allByCliente(int $clienteId): array
    {
        $stmt = $this->dbCentral->prepare(
            'SELECT v.*, c.nombre AS cliente, s.nombre AS sucursal
             FROM ventas v
             INNER JOIN clientes c ON c.id_cliente = v.id_cliente
             INNER JOIN sucursales s ON s.id_sucursal = v.id_sucursal
             WHERE v.id_cliente = :id_cliente
             ORDER BY v.id_venta DESC'
        );
        $stmt->execute(['id_cliente' => $clienteId]);
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->dbCentral->prepare('SELECT * FROM ventas WHERE id_venta = :id');
        $stmt->execute(['id' => $id]);
        $venta = $stmt->fetch();
        if (!$venta) {
            return null;
        }

        $detailStmt = $this->dbCentral->prepare('SELECT * FROM detalle_venta WHERE id_venta = :id');
        $detailStmt->execute(['id' => $id]);
        $venta['detalle'] = $detailStmt->fetchAll();
        return $venta;
    }

    public function create(array $data): void
    {
        $this->persist(null, $data);
    }

    public function update(int $id, array $data): void
    {
        $this->persist($id, $data);
    }

    public function delete(int $id): void
    {
        $venta = $this->find($id);
        if (!$venta) {
            throw new RuntimeException('Venta no encontrada.');
        }

        $this->assertBranchAvailable((int) $venta['id_sucursal']);

        $branchDb = dbSucursalById((int) $venta['id_sucursal']);
        $this->dbCentral->beginTransaction();
        $branchDb->beginTransaction();

        try {
            foreach ($venta['detalle'] as $item) {
                $this->callBranchAdjustStock(
                    $branchDb,
                    (int) $item['id_producto'],
                    (int) $venta['id_sucursal'],
                    (int) $item['cantidad'],
                    uniqid('rollback_', true)
                );
            }

            $stmt = $this->dbCentral->prepare('DELETE FROM ventas WHERE id_venta = :id');
            $stmt->execute(['id' => $id]);

            $branchDb->commit();
            $this->dbCentral->commit();
        } catch (Throwable $e) {
            if ($branchDb->inTransaction()) {
                $branchDb->rollBack();
            }
            if ($this->dbCentral->inTransaction()) {
                $this->dbCentral->rollBack();
            }
            throw $e;
        }
    }

    private function persist(?int $id, array $data): void
    {
        $items = $data['items'];
        $sucursalId = (int) $data['id_sucursal'];
        $transactionCode = uniqid('txn_', true);
        $this->assertBranchAvailable($sucursalId);
        $branchDb = dbSucursalById($sucursalId);

        $prepared = $this->dbCentral->prepare(
            'INSERT INTO distributed_transactions (transaction_code, operation_type, status, payload)
             VALUES (:code, :type, :status, :payload)'
        );
        $prepared->execute([
            'code' => $transactionCode,
            'type' => $id === null ? 'VENTA_CREATE' : 'VENTA_UPDATE',
            'status' => 'PREPARED',
            'payload' => json_encode($data, JSON_UNESCAPED_UNICODE),
        ]);

        $this->dbCentral->beginTransaction();
        $branchDb->beginTransaction();

        try {
            if ($id !== null) {
                $previous = $this->find($id);
                if (!$previous) {
                    throw new RuntimeException('Venta no encontrada.');
                }

                if ((int) $previous['id_sucursal'] !== $sucursalId) {
                    throw new RuntimeException('Para simplificar la transaccion distribuida, una venta editada debe mantenerse en la misma sucursal.');
                }

                foreach ($previous['detalle'] as $item) {
                    $this->callBranchAdjustStock(
                        $branchDb,
                        (int) $item['id_producto'],
                        (int) $previous['id_sucursal'],
                        (int) $item['cantidad'],
                        $transactionCode
                    );
                }

                $update = $this->dbCentral->prepare(
                    'UPDATE ventas SET id_cliente = :cliente, id_sucursal = :sucursal, fecha = :fecha WHERE id_venta = :id'
                );
                $update->execute([
                    'cliente' => (int) $data['id_cliente'],
                    'sucursal' => $sucursalId,
                    'fecha' => $data['fecha'],
                    'id' => $id,
                ]);

                $delete = $this->dbCentral->prepare('DELETE FROM detalle_venta WHERE id_venta = :id');
                $delete->execute(['id' => $id]);
                $ventaId = $id;
            } else {
                $ventaId = $this->callCentralCreateVenta(
                    (int) $data['id_cliente'],
                    $sucursalId,
                    (string) $data['fecha']
                );
            }

            foreach ($items as $item) {
                $producto = $this->productoModel->find((int) $item['id_producto']);
                if (!$producto || (int) $producto['activo'] !== 1) {
                    throw new RuntimeException('Existe un producto invalido en la venta.');
                }

                $this->callBranchPurchaseProcedure(
                    $branchDb,
                    $transactionCode,
                    (int) $item['id_producto'],
                    $sucursalId,
                    (int) $item['cantidad']
                );

                $this->callCentralDetalleVenta(
                    $ventaId,
                    (int) $item['id_producto'],
                    (int) $item['cantidad'],
                    (float) $producto['precio']
                );
            }

            $this->callCentralUpdateVentaTotal($ventaId);

            /*
             * Atomicidad: la venta central y el descuento de stock local se confirman juntas o se revierten completas.
             * Consistencia: se valida stock en la sucursal antes de descontar y nunca se registra una venta con inventario invalido.
             * Aislamiento: la transaccion sobre el nodo central y el nodo de sucursal evita estados parciales visibles.
             * Durabilidad: tras commit, InnoDB persiste tanto la venta como el nuevo stock en sus nodos respectivos.
             *
             * Two Phase Commit simplificado:
             * 1. PREPARED: se registra la intencion en distributed_transactions del nodo central.
             * 2. COMMIT: si nodo central y nodo sucursal responden bien, ambos confirman.
             * 3. ABORTED: si falla cualquiera de los dos, se hace rollback total para preservar CP.
             */
            $branchDb->commit();
            $this->dbCentral->commit();

            $committed = $this->dbCentral->prepare(
                'UPDATE distributed_transactions SET status = :status WHERE transaction_code = :code'
            );
            $committed->execute([
                'status' => 'COMMITTED',
                'code' => $transactionCode,
            ]);
        } catch (Throwable $e) {
            if ($branchDb->inTransaction()) {
                $branchDb->rollBack();
            } else {
                $this->tryRecoverBranchStock($branchDb, $transactionCode);
            }
            if ($this->dbCentral->inTransaction()) {
                $this->dbCentral->rollBack();
            }

            $aborted = $this->dbCentral->prepare(
                'UPDATE distributed_transactions SET status = :status WHERE transaction_code = :code'
            );
            $aborted->execute([
                'status' => 'ABORTED',
                'code' => $transactionCode,
            ]);

            throw $e;
        }
    }

    private function assertBranchAvailable(int $sucursalId): void
    {
        if (!$this->nodoModel->isAvailableForOperations($sucursalId)) {
            $node = $this->nodoModel->statusSnapshot($sucursalId);

            if ($node && (int) ($node['logical_online'] ?? 0) === 1 && (int) ($node['reachable'] ?? 0) === 0) {
                throw new RuntimeException('La sucursal seleccionada figura ONLINE, pero no tiene conectividad real. La venta se bloquea para preservar consistencia CP.');
            }

            throw new RuntimeException('La sucursal seleccionada esta OFFLINE. Bajo CP la venta se bloquea para evitar inconsistencias.');
        }
    }

    private function callCentralCreateVenta(int $clienteId, int $sucursalId, string $fecha): int
    {
        $stmt = $this->dbCentral->prepare('CALL sp_registrar_venta(:cliente, :sucursal, :fecha)');
        $stmt->execute([
            'cliente' => $clienteId,
            'sucursal' => $sucursalId,
            'fecha' => $fecha,
        ]);
        $row = $stmt->fetch();
        $stmt->closeCursor();

        if (!$row || !isset($row['id_venta'])) {
            throw new RuntimeException('No fue posible registrar la cabecera de la venta.');
        }

        return (int) $row['id_venta'];
    }

    private function callCentralDetalleVenta(int $ventaId, int $productoId, int $cantidad, float $precio): void
    {
        $stmt = $this->dbCentral->prepare(
            'CALL sp_registrar_detalle_venta(:venta, :producto, :cantidad, :precio)'
        );
        $stmt->execute([
            'venta' => $ventaId,
            'producto' => $productoId,
            'cantidad' => $cantidad,
            'precio' => $precio,
        ]);
        $stmt->closeCursor();
    }

    private function callCentralUpdateVentaTotal(int $ventaId): void
    {
        $stmt = $this->dbCentral->prepare('CALL sp_actualizar_total_venta(:venta)');
        $stmt->execute(['venta' => $ventaId]);
        $stmt->closeCursor();
    }

    private function callBranchPurchaseProcedure(PDO $branchDb, string $transactionCode, int $productoId, int $sucursalId, int $cantidad): void
    {
        $stmt = $branchDb->prepare(
            'CALL sp_realizar_compra(:code, :producto, :sucursal, :cantidad)'
        );
        $stmt->execute([
            'code' => $transactionCode,
            'producto' => $productoId,
            'sucursal' => $sucursalId,
            'cantidad' => $cantidad,
        ]);
        $stmt->closeCursor();
    }

    private function callBranchAdjustStock(PDO $branchDb, int $productoId, int $sucursalId, int $cantidad, string $transactionCode): void
    {
        $stmt = $branchDb->prepare(
            'CALL sp_actualizar_stock(:producto, :sucursal, :delta, :code)'
        );
        $stmt->execute([
            'producto' => $productoId,
            'sucursal' => $sucursalId,
            'delta' => $cantidad,
            'code' => $transactionCode,
        ]);
        $stmt->closeCursor();
    }

    private function tryRecoverBranchStock(PDO $branchDb, string $transactionCode): void
    {
        try {
            $stmt = $branchDb->prepare('CALL sp_reconstruir_stock(:code)');
            $stmt->execute(['code' => $transactionCode]);
            $stmt->closeCursor();
        } catch (Throwable) {
        }
    }
}
