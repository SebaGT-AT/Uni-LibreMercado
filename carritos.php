<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/models/Carrito.php';
require_once __DIR__ . '/models/Cliente.php';
require_once __DIR__ . '/models/Producto.php';
require_once __DIR__ . '/models/Sucursal.php';
require_once __DIR__ . '/models/Venta.php';
require_once __DIR__ . '/models/Stock.php';

requireLogin();

$model = new Carrito();
$clienteModel = new Cliente();
$productoModel = new Producto();
$sucursalModel = new Sucursal();
$ventaModel = new Venta();
$stockModel = new Stock();
$errors = [];
$currentClient = isClientRole() ? $clienteModel->findByUsuarioId((int) currentUser()['id_usuario']) : null;

if (isClientRole() && !$currentClient) {
    setFlash('danger', 'Tu usuario no tiene un cliente asociado. Contacta al administrador.');
    redirect('dashboard.php');
}

$editId = isset($_GET['edit']) ? (string) $_GET['edit'] : null;
$checkoutId = isset($_GET['checkout']) ? (string) $_GET['checkout'] : null;
$stockSucursalId = isset($_GET['stock_sucursal']) ? (int) $_GET['stock_sucursal'] : 0;

$editing = null;
if ($editId) {
    $editing = isClientRole()
        ? $model->findForCliente($editId, (int) $currentClient['id_cliente'])
        : $model->find($editId);

    if (!$editing) {
        setFlash('warning', 'El carrito que intenta editar no existe o no te pertenece.');
        redirect('carritos.php');
    }
}

$checkoutCart = null;
if ($checkoutId) {
    $checkoutCart = isClientRole()
        ? $model->findForCliente($checkoutId, (int) $currentClient['id_cliente'])
        : $model->find($checkoutId);

    if (!$checkoutCart) {
        setFlash('warning', 'El carrito que intenta comprar no existe o no te pertenece.');
        redirect('carritos.php');
    }
}

if (isset($_GET['delete'])) {
    $deleteId = (string) $_GET['delete'];

    try {
        if (isClientRole()) {
            $model->deleteForCliente($deleteId, (int) $currentClient['id_cliente']);
        } else {
            $model->delete($deleteId);
        }
        setFlash('success', 'Carrito eliminado correctamente.');
    } catch (Throwable) {
        setFlash('danger', 'No fue posible eliminar el carrito.');
    }

    redirect('carritos.php');
}

if (isPost() && (($_POST['action'] ?? '') === 'checkout')) {
    $errors = requireFields($_POST, [
        'checkout_id' => 'carrito',
    ]);

    $checkoutCart = !$errors
        ? (isClientRole()
            ? $model->findForCliente((string) $_POST['checkout_id'], (int) $currentClient['id_cliente'])
            : $model->find((string) $_POST['checkout_id']))
        : null;

    if (!$checkoutCart) {
        $errors[] = 'El carrito seleccionado no existe o no te pertenece.';
    }

    if (!$errors) {
        try {
            $items = array_map(
                static fn(array $item): array => [
                    'id_producto' => (int) $item['id_producto'],
                    'cantidad' => (int) $item['cantidad'],
                ],
                $checkoutCart['detalle']
            );

            $payload = [
                'id_cliente' => (int) $checkoutCart['id_cliente'],
                'id_sucursal' => (int) $checkoutCart['id_sucursal'],
                'fecha' => date('Y-m-d H:i:s'),
                'items' => $items,
            ];

            $ventaModel->create($payload);

            if (isClientRole()) {
                $model->deleteForCliente((string) $checkoutCart['global_id'], (int) $currentClient['id_cliente']);
            } else {
                $model->delete((string) $checkoutCart['global_id']);
            }

            setFlash('success', 'Compra confirmada correctamente. La venta fue registrada y el carrito se cerro.');
            redirect('carritos.php');
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
}

if (isPost() && (($_POST['action'] ?? '') !== 'checkout')) {
    $errors = requireFields($_POST, [
        'fecha' => 'fecha',
        'id_sucursal' => 'sucursal',
    ]);

    if (!isClientRole()) {
        $clientErrors = requireFields($_POST, ['id_cliente' => 'cliente']);
        $errors = array_merge($errors, $clientErrors);
    }

    $items = normalizeItems($_POST['id_producto'] ?? [], $_POST['cantidad'] ?? []);
    if (!$items) {
        $errors[] = 'Debe agregar al menos un producto al carrito.';
    }

    if (!$errors) {
        try {
            $payload = [
                'id_cliente' => isClientRole() ? (int) $currentClient['id_cliente'] : (int) $_POST['id_cliente'],
                'id_sucursal' => (int) $_POST['id_sucursal'],
                'fecha' => str_replace('T', ' ', (string) $_POST['fecha']),
                'items' => $items,
            ];

            if (!empty($_POST['id'])) {
                $targetCart = isClientRole()
                    ? $model->findForCliente((string) $_POST['id'], (int) $currentClient['id_cliente'])
                    : $model->find((string) $_POST['id']);

                if (!$targetCart) {
                    throw new RuntimeException('El carrito que intenta actualizar no existe o no te pertenece.');
                }

                $model->update((string) $_POST['id'], $payload);
            } else {
                $model->create($payload);
            }

            setFlash('success', 'Carrito guardado correctamente.');
            redirect('carritos.php');
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
}

$currentModule = 'carritos';
$clientes = isAdmin() ? $clienteModel->all() : [];
$productos = $productoModel->active();
$sucursales = $sucursalModel->all();
$carritos = isClientRole() ? $model->allByCliente((int) $currentClient['id_cliente']) : $model->all();
$selectedStockSucursalId = $editing
    ? (int) $editing['id_sucursal']
    : ($stockSucursalId > 0 ? $stockSucursalId : 0);
$stockSnapshot = [];

if ($selectedStockSucursalId > 0) {
    try {
        $stockSnapshot = $stockModel->availabilityBySucursal($selectedStockSucursalId);
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }
}

$stockByProductId = [];
foreach ($stockSnapshot as $stockItem) {
    $stockByProductId[(int) $stockItem['id_producto']] = $stockItem;
}

require __DIR__ . '/includes/header.php';
?>
<?php require __DIR__ . '/includes/flash.php'; ?>

<?php foreach ($errors as $error): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endforeach; ?>

<div id="checkout-alert"></div>

<?php if (isClientRole()): ?>
    <?php
    $selectedBranchName = $selectedStockSucursalId > 0 ? ($sucursalModel->find($selectedStockSucursalId)['nombre'] ?? 'Sucursal seleccionada') : 'Selecciona una sucursal';
    $cartDraft = array_values(array_filter(
        $editing['detalle'] ?? [],
        static fn(array $row): bool => (int) ($row['id_producto'] ?? 0) > 0
    ));
    ?>

    <section class="client-page-header">
        <div>
            <h1>Comprar productos</h1>
            <p>Selecciona una sucursal, busca un producto, elige cantidad y agrégalo al carrito.</p>
        </div>
        <div class="client-page-actions">
            <a href="mis_compras.php" class="btn btn-outline-secondary">Mis compras</a>
        </div>
    </section>

    <section class="client-toolbar mb-3">
        <div class="client-toolbar-group">
            <label for="stock-sucursal-selector" class="form-label mb-0">Sucursal</label>
            <select id="stock-sucursal-selector" class="form-select client-branch-select">
                <option value="">Seleccione</option>
                <?php foreach ($sucursales as $sucursal): ?>
                    <option value="<?= (int) $sucursal['id_sucursal'] ?>" <?= $selectedStockSucursalId === (int) $sucursal['id_sucursal'] ? 'selected' : '' ?>>
                        <?= e($sucursal['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <input type="search" class="form-control" placeholder="Buscar producto" data-catalog-search data-catalog-target="#client-product-list">
        <div class="client-toolbar-meta">
            <span><?= e($selectedBranchName) ?></span>
            <?php if ($selectedStockSucursalId > 0): ?><span id="stock-updated-badge">Actualizado: <?= e(date('H:i')) ?></span><?php endif; ?>
        </div>
    </section>

    <div class="client-shop-layout">
        <section class="client-panel">
            <div class="table-responsive">
                <table class="table client-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Precio</th>
                            <th>Disponible</th>
                            <th>Cantidad</th>
                            <th class="text-end"></th>
                        </tr>
                    </thead>
                    <tbody id="client-product-list">
                        <?php foreach ($productos as $producto): ?>
                            <?php
                            $productId = (int) $producto['id_producto'];
                            $stockInfo = $stockByProductId[$productId] ?? null;
                            $stockAmount = $stockInfo ? (int) $stockInfo['cantidad'] : null;
                            $buttonDisabled = $selectedStockSucursalId <= 0 || ($stockAmount !== null && $stockAmount <= 0);
                            ?>
                            <tr
                                data-product-row
                                data-product-id="<?= $productId ?>"
                                data-searchable="<?= e(strtolower($producto['producto'] . ' ' . (string) $producto['descripcion'])) ?>"
                            >
                                <td>
                                    <div class="fw-semibold"><?= e($producto['producto']) ?></div>
                                    <div class="text-muted small"><?= e((string) ($producto['descripcion'] ?: 'Sin descripcion')) ?></div>
                                </td>
                                <td>$<?= number_format((float) $producto['precio'], 0, ',', '.') ?></td>
                                <td data-stock-cell="<?= $productId ?>">
                                    <?php if ($selectedStockSucursalId <= 0): ?>
                                        <span class="text-muted">Seleccione sucursal</span>
                                    <?php elseif ($stockAmount === null): ?>
                                        <span class="text-muted">Sin datos</span>
                                    <?php else: ?>
                                        <span class="<?= $stockAmount > 0 ? 'text-success' : 'text-danger' ?> fw-semibold"><?= $stockAmount ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="client-qty-cell">
                                    <input type="number" min="1" value="1" class="form-control form-control-sm" data-quick-qty="<?= $productId ?>">
                                </td>
                                <td class="text-end">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-primary"
                                        data-quick-add
                                        data-product-id="<?= $productId ?>"
                                        data-product-name="<?= e($producto['producto']) ?>"
                                        <?= $buttonDisabled ? 'disabled' : '' ?>
                                    >
                                        Agregar
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <aside class="client-panel">
            <form method="post" id="client-cart-form">
                <input type="hidden" name="id" value="<?= e($editing['global_id'] ?? '') ?>">
                <input type="hidden" name="id_sucursal" value="<?= $selectedStockSucursalId > 0 ? $selectedStockSucursalId : '' ?>" data-form-sucursal-input>
                <input type="hidden" name="fecha" value="<?= e(date('Y-m-d\TH:i')) ?>">

                <div class="client-cart-header">
                    <h2 class="h5 mb-1">Carrito</h2>
                    <p class="text-muted mb-0">Agrega productos y guarda el carrito para revisarlo o comprarlo después.</p>
                </div>

                <div id="carrito-items" class="client-cart-items">
                    <?php foreach ($cartDraft as $row): ?>
                        <?php
                        $rowProductId = (int) ($row['id_producto'] ?? 0);
                        $rowName = '';
                        foreach ($productos as $producto) {
                            if ((int) $producto['id_producto'] === $rowProductId) {
                                $rowName = (string) $producto['producto'];
                                break;
                            }
                        }
                        ?>
                        <div class="client-cart-row dynamic-row" data-cart-row>
                            <div class="client-cart-row-main">
                                <div class="client-cart-product"><?= e($rowName !== '' ? $rowName : 'Producto') ?></div>
                                <select name="id_producto[]" class="form-select form-select-sm d-none" data-cart-product-select>
                                    <option value="">Producto</option>
                                    <?php foreach ($productos as $producto): ?>
                                        <option value="<?= $producto['id_producto'] ?>" <?= $rowProductId === (int) $producto['id_producto'] ? 'selected' : '' ?>><?= e($producto['producto']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="number" name="cantidad[]" class="form-control form-control-sm client-cart-qty" min="1" value="<?= e((string) ($row['cantidad'] ?? '1')) ?>">
                            </div>
                            <button type="button" class="btn btn-outline-danger btn-sm" data-remove-row>Quitar</button>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="client-cart-actions">
                    <button class="btn btn-primary w-100">Guardar carrito</button>
                </div>
            </form>
        </aside>
    </div>

    <section class="client-panel mt-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <h2 class="h5 mb-1">Carritos guardados</h2>
                <p class="text-muted mb-0">Edita un carrito guardado o finaliza la compra.</p>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table client-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Carrito</th>
                        <th>Sucursal</th>
                        <th>Fecha</th>
                        <th class="text-end"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($carritos as $item): ?>
                        <tr>
                            <td><?= e($item['global_id']) ?></td>
                            <td><?= e($item['sucursal']) ?></td>
                            <td><?= e($item['fecha']) ?></td>
                            <td class="text-end">
                                <div class="client-table-actions">
                                    <a href="carritos.php?edit=<?= urlencode((string) $item['global_id']) ?>" class="btn btn-sm btn-outline-secondary">Editar</a>
                                    <a href="carritos.php?checkout=<?= urlencode((string) $item['global_id']) ?>" class="btn btn-sm btn-primary">Comprar</a>
                                    <a href="carritos.php?delete=<?= urlencode((string) $item['global_id']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Eliminar carrito?')">Eliminar</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$carritos): ?>
                        <tr>
                            <td colspan="4" class="text-muted">Todavia no tienes carritos guardados.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <?php if ($checkoutCart): ?>
        <section class="client-panel mt-4">
            <h2 class="h5 mb-3">Finalizar compra</h2>
            <p class="text-muted">La venta se registrara en la sucursal asignada al carrito: <strong><?= e($sucursalModel->find((int) $checkoutCart['id_sucursal'])['nombre'] ?? '') ?></strong>.</p>
            <form method="post" class="row g-3" data-ajax-checkout>
                <input type="hidden" name="action" value="checkout">
                <input type="hidden" name="checkout_id" value="<?= e($checkoutCart['global_id']) ?>">
                <div class="col-12 d-flex gap-2 flex-wrap">
                    <button class="btn btn-success">Finalizar compra</button>
                    <a href="carritos.php" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </section>
    <?php endif; ?>

<?php else: ?>
    <div class="page-title">
        <h1 class="h3 mb-0">CRUD Carritos</h1>
        <a href="dashboard.php" class="btn btn-outline-secondary">Volver</a>
    </div>

    <section class="card card-shadow border-0 mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <h2 class="h5 mb-1">Stock disponible para clientes</h2>
                    <p class="text-muted mb-0">Selecciona una sucursal para ver el stock real disponible antes de agregar productos o confirmar una compra.</p>
                </div>
                <?php if ($selectedStockSucursalId > 0): ?>
                    <span class="badge text-bg-primary" id="stock-updated-badge">Actualizado: <?= e(date('H:i')) ?></span>
                <?php endif; ?>
            </div>
            <div class="row g-3 mt-1">
                <div class="col-md-4">
                    <label for="stock-sucursal-selector" class="form-label">Sucursal a consultar</label>
                    <select id="stock-sucursal-selector" class="form-select">
                        <option value="">Seleccione</option>
                        <?php foreach ($sucursales as $sucursal): ?>
                            <option value="<?= (int) $sucursal['id_sucursal'] ?>" <?= $selectedStockSucursalId === (int) $sucursal['id_sucursal'] ? 'selected' : '' ?>>
                                <?= e($sucursal['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div id="stock-panel" class="mt-3">
                <?php if ($selectedStockSucursalId > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Precio</th>
                                    <th>Stock</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($stockSnapshot as $item): ?>
                                <tr>
                                    <td><?= e($item['producto']) ?></td>
                                    <td>$<?= number_format((float) $item['precio'], 0, ',', '.') ?></td>
                                    <td><?= (int) $item['cantidad'] ?></td>
                                    <td><span class="badge <?= (int) $item['cantidad'] > 0 ? 'text-bg-success' : 'text-bg-danger' ?>"><?= (int) $item['cantidad'] > 0 ? 'Disponible' : 'Sin stock' ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-secondary mb-0">Todavia no has seleccionado una sucursal para consultar stock.</div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php if ($checkoutCart): ?>
        <section class="card card-shadow border-0 mb-4">
            <div class="card-body">
                <h2 class="h5 mb-3">Confirmar compra desde carrito #<?= e($checkoutCart['global_id']) ?></h2>
                <p class="text-muted">La venta se registrara en la sucursal asignada al carrito: <strong><?= e($sucursalModel->find((int) $checkoutCart['id_sucursal'])['nombre'] ?? '') ?></strong>.</p>
                <form method="post" class="row g-3" data-ajax-checkout>
                    <input type="hidden" name="action" value="checkout">
                    <input type="hidden" name="checkout_id" value="<?= e($checkoutCart['global_id']) ?>">
                    <div class="col-12 d-flex gap-2 flex-wrap">
                        <button class="btn btn-success">Confirmar compra</button>
                        <a href="carritos.php" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </section>
    <?php endif; ?>

    <div class="card card-shadow border-0 mb-4">
        <div class="card-body">
            <form method="post" class="row g-3">
                <input type="hidden" name="id" value="<?= e($editing['global_id'] ?? '') ?>">
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Cliente</label>
                        <select name="id_cliente" class="form-select">
                            <option value="">Seleccione</option>
                            <?php $clienteActual = (string) ($editing['id_cliente'] ?? old('id_cliente')); ?>
                            <?php foreach ($clientes as $cliente): ?>
                                <option value="<?= $cliente['id_cliente'] ?>" <?= $clienteActual === (string) $cliente['id_cliente'] ? 'selected' : '' ?>><?= e($cliente['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Sucursal</label>
                        <select name="id_sucursal" class="form-select" data-form-sucursal>
                            <option value="">Seleccione</option>
                            <?php $sucursalActual = (string) ($editing['id_sucursal'] ?? old('id_sucursal')); ?>
                            <?php foreach ($sucursales as $sucursal): ?>
                                <option value="<?= $sucursal['id_sucursal'] ?>" <?= $sucursalActual === (string) $sucursal['id_sucursal'] ? 'selected' : '' ?>><?= e($sucursal['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Fecha</label>
                        <input type="datetime-local" name="fecha" class="form-control" value="<?= e(str_replace(' ', 'T', substr((string) ($editing['fecha'] ?? old('fecha', date('Y-m-d\TH:i'))), 0, 16))) ?>">
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="h5 mb-0">Detalle</h2>
                    <button type="button" class="btn btn-outline-primary btn-sm" data-add-row="#carrito-items" data-template="#carrito-template">Agregar fila</button>
                </div>

                <div id="carrito-items">
                    <?php $detalle = $editing['detalle'] ?? [['id_producto' => '', 'cantidad' => '']]; ?>
                    <?php foreach ($detalle as $row): ?>
                        <div class="row g-2 dynamic-row mb-2">
                            <div class="col-md-7">
                                <select name="id_producto[]" class="form-select">
                                    <option value="">Producto</option>
                                    <?php foreach ($productos as $producto): ?>
                                        <option value="<?= $producto['id_producto'] ?>" <?= (string) $row['id_producto'] === (string) $producto['id_producto'] ? 'selected' : '' ?>><?= e($producto['producto']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <input type="number" name="cantidad[]" class="form-control" placeholder="Cantidad" value="<?= e((string) ($row['cantidad'] ?? '')) ?>">
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn btn-outline-danger w-100" data-remove-row>x</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <button class="btn btn-primary mt-3"><?= $editing ? 'Actualizar' : 'Crear' ?></button>
            </form>
        </div>
    </div>

    <div class="card card-shadow border-0">
        <div class="card-body table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID Distribuido</th>
                        <th>Cliente</th>
                        <th>Sucursal</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($carritos as $item): ?>
                    <tr>
                        <td><?= e($item['global_id']) ?></td>
                        <td><?= e($item['nombre']) ?></td>
                        <td><?= e($item['sucursal']) ?></td>
                        <td><?= e($item['fecha']) ?></td>
                        <td>
                            <a href="carritos.php?edit=<?= urlencode((string) $item['global_id']) ?>" class="btn btn-sm btn-warning">Editar</a>
                            <a href="carritos.php?checkout=<?= urlencode((string) $item['global_id']) ?>" class="btn btn-sm btn-success">Confirmar compra</a>
                            <a href="carritos.php?delete=<?= urlencode((string) $item['global_id']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Eliminar carrito?')">Eliminar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<template id="carrito-template">
    <?php if (isClientRole()): ?>
        <div class="client-cart-row dynamic-row" data-cart-row>
            <div class="client-cart-row-main">
                <div class="client-cart-product">Producto</div>
                <select name="id_producto[]" class="form-select form-select-sm d-none" data-cart-product-select>
                    <option value="">Producto</option>
                    <?php foreach ($productos as $producto): ?>
                        <option value="<?= $producto['id_producto'] ?>"><?= e($producto['producto']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="number" name="cantidad[]" class="form-control form-control-sm client-cart-qty" min="1" value="1">
            </div>
            <button type="button" class="btn btn-outline-danger btn-sm" data-remove-row>Quitar</button>
        </div>
    <?php else: ?>
        <div class="row g-2 dynamic-row mb-2">
            <div class="col-md-7">
                <select name="id_producto[]" class="form-select">
                    <option value="">Producto</option>
                    <?php foreach ($productos as $producto): ?>
                        <option value="<?= $producto['id_producto'] ?>"><?= e($producto['producto']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <input type="number" name="cantidad[]" class="form-control" placeholder="Cantidad">
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-outline-danger w-100" data-remove-row>x</button>
            </div>
        </div>
    <?php endif; ?>
</template>

<script>
async function loadSucursalStock(sucursalId) {
    const badge = document.getElementById('stock-updated-badge');
    const panel = document.getElementById('stock-panel');
    const clientMode = document.body.classList.contains('client-shell');
    const branchInput = document.querySelector('[data-form-sucursal-input]');

    if (!sucursalId) {
        if (branchInput) {
            branchInput.value = '';
        }
        if (clientMode) {
            document.querySelectorAll('[data-stock-cell]').forEach(function (cell) {
                cell.innerHTML = '<span class="text-muted">Seleccione sucursal</span>';
            });
            document.querySelectorAll('[data-quick-add]').forEach(function (button) {
                button.disabled = true;
            });
        } else if (panel) {
            panel.innerHTML = '<div class="alert alert-secondary mb-0">Todavia no has seleccionado una sucursal para consultar stock.</div>';
        }
        if (badge) {
            badge.remove();
        }
        return;
    }

    if (branchInput) {
        branchInput.value = sucursalId;
    }

    if (!clientMode && panel) {
        panel.innerHTML = '<div class="alert alert-info mb-0">Consultando stock disponible...</div>';
    }

    try {
        const response = await fetch(`api/stock_disponible.php?id_sucursal=${encodeURIComponent(sucursalId)}`);
        const data = await response.json();

        if (!data.ok) {
            if (clientMode) {
                document.querySelectorAll('[data-stock-cell]').forEach(function (cell) {
                    cell.innerHTML = '<span class="text-danger">Sin datos</span>';
                });
            } else if (panel) {
                panel.innerHTML = `<div class="alert alert-danger mb-0">${data.message}</div>`;
            }
            return;
        }

        if (clientMode) {
            const stockMap = new Map((data.items || []).map((item) => [String(item.id_producto), item]));

            document.querySelectorAll('[data-product-row]').forEach(function (row) {
                const productId = row.dataset.productId;
                const cell = row.querySelector(`[data-stock-cell="${productId}"]`);
                const addButton = row.querySelector('[data-quick-add]');
                const item = stockMap.get(String(productId));

                if (!cell) {
                    return;
                }

                if (!item) {
                    cell.innerHTML = '<span class="text-muted">Sin datos</span>';
                    if (addButton) {
                        addButton.disabled = true;
                    }
                    return;
                }

                const available = Number(item.cantidad || 0);
                cell.innerHTML = `<span class="${available > 0 ? 'text-success' : 'text-danger'} fw-semibold">${available}</span>`;

                if (addButton) {
                    addButton.disabled = available <= 0;
                }
            });
        } else {
            const rows = (data.items || []).map((item) => `
                <tr>
                    <td>${item.producto}</td>
                    <td>$${Number(item.precio || 0).toLocaleString('es-CL')}</td>
                    <td>${item.cantidad}</td>
                    <td><span class="badge ${Number(item.cantidad) > 0 ? 'text-bg-success' : 'text-bg-danger'}">${Number(item.cantidad) > 0 ? 'Disponible' : 'Sin stock'}</span></td>
                </tr>
            `).join('');

            panel.innerHTML = `
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Precio</th>
                                <th>Stock</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>${rows}</tbody>
                    </table>
                </div>
            `;
        }

        const label = badge || (() => {
            const created = document.createElement('span');
            created.id = 'stock-updated-badge';
            created.className = clientMode ? 'text-muted small' : 'badge text-bg-primary';
            const target = document.querySelector('.client-toolbar-meta') || document.querySelector('#stock-panel')?.closest('.card-body')?.querySelector('.d-flex');
            target?.appendChild(created);
            return created;
        })();
        label.textContent = `Actualizado: ${data.updated_at}`;
    } catch (error) {
        if (!clientMode && panel) {
            panel.innerHTML = '<div class="alert alert-danger mb-0">No fue posible consultar el stock disponible.</div>';
        }
    }
}

document.getElementById('stock-sucursal-selector')?.addEventListener('change', function (event) {
    loadSucursalStock(event.target.value);
});

document.addEventListener('click', function (event) {
    const quickAdd = event.target.closest('[data-quick-add]');

    if (!quickAdd) {
        return;
    }

    const branchSelector = document.getElementById('stock-sucursal-selector');
    const branchInput = document.querySelector('[data-form-sucursal-input]');

    if (!branchSelector || branchSelector.value === '') {
        alert('Selecciona una sucursal antes de agregar productos.');
        return;
    }

    if (branchInput) {
        branchInput.value = branchSelector.value;
    }

    const productId = quickAdd.dataset.productId;
    const productName = quickAdd.dataset.productName || 'Producto';
    const qtyInput = document.querySelector(`[data-quick-qty="${productId}"]`);
    const quantity = Math.max(1, Number(qtyInput?.value || 1));
    const existingSelect = document.querySelector(`#carrito-items [data-cart-product-select] option:checked[value="${productId}"]`);

    if (existingSelect) {
        const row = existingSelect.closest('[data-cart-row]');
        const currentQty = row?.querySelector('.client-cart-qty');
        if (currentQty) {
            currentQty.value = Number(currentQty.value || 0) + quantity;
        }
        return;
    }

    const template = document.getElementById('carrito-template');
    const target = document.getElementById('carrito-items');

    if (!template || !target) {
        return;
    }

    target.insertAdjacentHTML('beforeend', template.innerHTML);
    const row = target.lastElementChild;
    const select = row?.querySelector('[data-cart-product-select]');
    const title = row?.querySelector('.client-cart-product');
    const qty = row?.querySelector('.client-cart-qty');

    if (select) {
        select.value = productId;
    }
    if (title) {
        title.textContent = productName;
    }
    if (qty) {
        qty.value = quantity;
    }
});

document.addEventListener('submit', async function (event) {
    const form = event.target.closest('[data-ajax-checkout]');
    if (!form) {
        return;
    }

    event.preventDefault();

    const alertBox = document.getElementById('checkout-alert');
    const submitButton = form.querySelector('button[type="submit"], button:not([type])');
    const formData = new URLSearchParams(new FormData(form));

    if (submitButton) {
        submitButton.disabled = true;
    }

    try {
        const response = await fetch('api/checkout.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
            },
            body: formData.toString(),
        });

        const data = await response.json();
        alertBox.innerHTML = `<div class="alert alert-${data.ok ? 'success' : 'danger'}">${data.message}</div>`;

        if (data.ok) {
            const stockSucursalSelector = document.getElementById('stock-sucursal-selector');
            if (stockSucursalSelector?.value) {
                await loadSucursalStock(stockSucursalSelector.value);
            }
            setTimeout(function () {
                window.location.href = data.redirect || 'carritos.php';
            }, 900);
        }
    } catch (error) {
        alertBox.innerHTML = '<div class="alert alert-danger">No fue posible procesar la compra distribuida.</div>';
    } finally {
        if (submitButton) {
            submitButton.disabled = false;
        }
    }
});
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
