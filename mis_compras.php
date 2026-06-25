<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/models/Cliente.php';
require_once __DIR__ . '/models/Venta.php';

requireLogin();

if (!isClientRole()) {
    setFlash('danger', 'Este modulo esta disponible solo para clientes.');
    redirect('dashboard.php');
}

$clienteModel = new Cliente();
$ventaModel = new Venta();
$currentClient = $clienteModel->findByUsuarioId((int) currentUser()['id_usuario']);

if (!$currentClient) {
    setFlash('danger', 'Tu usuario no tiene un cliente asociado. Contacta al administrador.');
    redirect('dashboard.php');
}

$ventas = $ventaModel->allByCliente((int) $currentClient['id_cliente']);
$selectedVenta = isset($_GET['ver']) ? $ventaModel->find((int) $_GET['ver']) : null;

if ($selectedVenta && (int) $selectedVenta['id_cliente'] !== (int) $currentClient['id_cliente']) {
    setFlash('warning', 'La compra solicitada no te pertenece.');
    redirect('mis_compras.php');
}

$currentModule = 'mis_compras';
require __DIR__ . '/includes/header.php';
?>
<?php require __DIR__ . '/includes/flash.php'; ?>
<div class="page-title">
    <h1 class="h3 mb-0">Mis Compras</h1>
    <a href="dashboard.php" class="btn btn-outline-secondary">Volver</a>
</div>

<?php if ($selectedVenta): ?>
    <div class="card card-shadow border-0 mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h2 class="h5 mb-1">Detalle de compra #<?= e($selectedVenta['id_venta']) ?></h2>
                    <p class="text-muted mb-0">Fecha: <?= e($selectedVenta['fecha']) ?></p>
                </div>
                <a href="mis_compras.php" class="btn btn-outline-secondary btn-sm">Cerrar detalle</a>
            </div>
            <div class="table-responsive mt-3">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Producto ID</th>
                            <th>Cantidad</th>
                            <th>Precio unitario</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($selectedVenta['detalle'] as $item): ?>
                        <tr>
                            <td><?= e($item['id_producto']) ?></td>
                            <td><?= e($item['cantidad']) ?></td>
                            <td>$<?= number_format((float) $item['precio'], 0, ',', '.') ?></td>
                            <td>$<?= number_format((float) $item['precio'] * (int) $item['cantidad'], 0, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="card card-shadow border-0">
    <div class="card-body">
        <?php if (!$ventas): ?>
            <p class="text-muted mb-0">Todavia no registras compras confirmadas.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>ID Venta</th>
                            <th>Sucursal</th>
                            <th>Fecha</th>
                            <th>Total</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($ventas as $venta): ?>
                        <tr>
                            <td><?= e($venta['id_venta']) ?></td>
                            <td><?= e($venta['sucursal']) ?></td>
                            <td><?= e($venta['fecha']) ?></td>
                            <td>$<?= number_format((float) $venta['total'], 0, ',', '.') ?></td>
                            <td>
                                <a href="mis_compras.php?ver=<?= $venta['id_venta'] ?>" class="btn btn-sm btn-primary">Ver detalle</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
