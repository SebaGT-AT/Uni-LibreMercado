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

<section class="client-page-header">
    <div>
        <h1>Mis compras</h1>
        <p>Revisa tus compras confirmadas y abre el detalle cuando lo necesites.</p>
    </div>
    <div class="client-page-actions">
        <a href="carritos.php" class="btn btn-primary">Seguir comprando</a>
    </div>
</section>

<?php if ($selectedVenta): ?>
    <section class="client-panel mb-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <h2 class="h4 mb-1">Compra #<?= e($selectedVenta['id_venta']) ?></h2>
                <p class="text-muted mb-0">Fecha: <?= e($selectedVenta['fecha']) ?></p>
            </div>
            <strong class="h5 mb-0">$<?= number_format((float) $selectedVenta['total'], 0, ',', '.') ?></strong>
            <a href="mis_compras.php" class="btn btn-outline-secondary btn-sm">Cerrar detalle</a>
        </div>
        <div class="table-responsive mt-3">
            <table class="table client-table align-middle mb-0">
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
    </section>
<?php endif; ?>

<section class="client-panel">
    <?php if (!$ventas): ?>
        <div class="text-muted">Todavia no registras compras confirmadas.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table client-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Sucursal</th>
                        <th>Fecha</th>
                        <th>Total</th>
                        <th class="text-end"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ventas as $venta): ?>
                        <tr>
                            <td><?= e($venta['id_venta']) ?></td>
                            <td><?= e($venta['sucursal']) ?></td>
                            <td><?= e($venta['fecha']) ?></td>
                            <td>$<?= number_format((float) $venta['total'], 0, ',', '.') ?></td>
                            <td class="text-end"><a href="mis_compras.php?ver=<?= $venta['id_venta'] ?>" class="btn btn-sm btn-outline-primary">Ver detalle</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
