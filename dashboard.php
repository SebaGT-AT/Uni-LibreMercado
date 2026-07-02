<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/models/Producto.php';
require_once __DIR__ . '/models/Cliente.php';
require_once __DIR__ . '/models/Venta.php';
require_once __DIR__ . '/models/Compra.php';
require_once __DIR__ . '/models/Carrito.php';

requireLogin();

$productoModel = new Producto();
$clienteModel = new Cliente();
$ventaModel = new Venta();
$compraModel = new Compra();
$carritoModel = new Carrito();
$currentClient = isClientRole() ? $clienteModel->findByUsuarioId((int) currentUser()['id_usuario']) : null;
$activeProducts = array_values(array_filter($productoModel->all(), static fn(array $item): bool => (int) $item['activo'] === 1));

$currentModule = 'dashboard';
require_once __DIR__ . '/includes/header.php';
?>
<?php require __DIR__ . '/includes/flash.php'; ?>

<?php if (isAdmin()): ?>
    <div class="page-title">
        <div>
            <h1 class="h3 mb-1">Dashboard distribuido</h1>
            <p class="text-muted mb-0">Vista general de los tres nodos del sistema.</p>
        </div>
    </div>
    <div class="row g-4">
        <div class="col-md-3">
            <div class="card card-shadow border-0"><div class="card-body"><h2 class="h6 text-muted">Productos activos</h2><div class="display-6"><?= count($activeProducts) ?></div></div></div>
        </div>
        <div class="col-md-3">
            <div class="card card-shadow border-0"><div class="card-body"><h2 class="h6 text-muted">Clientes</h2><div class="display-6"><?= count($clienteModel->all()) ?></div></div></div>
        </div>
        <div class="col-md-3">
            <div class="card card-shadow border-0"><div class="card-body"><h2 class="h6 text-muted">Ventas</h2><div class="display-6"><?= count($ventaModel->all()) ?></div></div></div>
        </div>
        <div class="col-md-3">
            <div class="card card-shadow border-0"><div class="card-body"><h2 class="h6 text-muted">Compras</h2><div class="display-6"><?= count($compraModel->all()) ?></div></div></div>
        </div>
    </div>
<?php else: ?>
    <?php
    $clientCarts = $currentClient ? $carritoModel->allByCliente((int) $currentClient['id_cliente']) : [];
    $clientOrders = $currentClient ? $ventaModel->allByCliente((int) $currentClient['id_cliente']) : [];
    ?>
    <section class="client-page-header">
        <div>
            <h1>Productos</h1>
            <p>Busca un producto y continúa al carrito para elegir cantidad, revisar el pedido y finalizar la compra.</p>
        </div>
        <div class="client-page-actions">
            <a href="carritos.php" class="btn btn-primary">Ir al carrito</a>
            <a href="mis_compras.php" class="btn btn-outline-secondary">Mis compras</a>
        </div>
    </section>

    <section class="client-toolbar mb-3">
        <input type="search" class="form-control" placeholder="Buscar producto" data-catalog-search data-catalog-target="#dashboard-product-list">
        <div class="client-toolbar-meta">
            <span><?= count($activeProducts) ?> productos</span>
            <span><?= count($clientCarts) ?> carritos</span>
            <span><?= count($clientOrders) ?> compras</span>
        </div>
    </section>

    <section class="client-panel">
        <div class="table-responsive">
            <table class="table client-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Descripcion</th>
                        <th>Precio</th>
                        <th class="text-end"></th>
                    </tr>
                </thead>
                <tbody id="dashboard-product-list">
                    <?php foreach ($activeProducts as $product): ?>
                        <tr data-product-row data-searchable="<?= e(strtolower($product['producto'] . ' ' . (string) $product['descripcion'])) ?>">
                            <td class="fw-semibold"><?= e($product['producto']) ?></td>
                            <td class="text-muted"><?= e((string) ($product['descripcion'] ?: 'Sin descripcion')) ?></td>
                            <td>$<?= number_format((float) $product['precio'], 0, ',', '.') ?></td>
                            <td class="text-end"><a href="carritos.php" class="btn btn-sm btn-outline-primary">Comprar</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
