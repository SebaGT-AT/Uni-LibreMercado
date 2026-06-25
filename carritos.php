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
    } catch (Throwable $e) {
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
                'fecha' => str_replace('T', ' ', $_POST['fecha']),
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
$stockSnapshot = $selectedStockSucursalId > 0 ? $stockModel->availabilityBySucursal($selectedStockSucursalId) : [];

require __DIR__ . '/includes/header.php';
?>
<?php require __DIR__ . '/includes/flash.php'; ?>
<div class="page-title">
    <h1 class="h3 mb-0"><?= isClientRole() ? 'Mis Carritos' : 'CRUD Carritos' ?></h1>
    <a href="dashboard.php" class="btn btn-outline-secondary">Volver</a>
</div>

<?php foreach ($errors as $error): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endforeach; ?>

<div id="checkout-alert"></div>

<div class="card card-shadow border-0 mb-4">
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
                                <td>
                                    <span class="badge <?= (int) $item['cantidad'] > 0 ? 'text-bg-success' : 'text-bg-danger' ?>">
                                        <?= (int) $item['cantidad'] > 0 ? 'Disponible' : 'Sin stock' ?>
                                    </span>
                                </td>
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
</div>

<?php if ($checkoutCart): ?>
    <div class="card card-shadow border-0 mb-4">
        <div class="card-body">
            <h2 class="h5 mb-3">Confirmar compra desde carrito #<?= e($checkoutCart['global_id']) ?></h2>
            <p class="text-muted">La venta se registrara en la sucursal asignada al carrito: <strong><?= e($sucursalModel->find((int) $checkoutCart['id_sucursal'])['nombre'] ?? '') ?></strong>.</p>
            <form method="post" class="row g-3" data-ajax-checkout>
                <input type="hidden" name="action" value="checkout">
                <input type="hidden" name="checkout_id" value="<?= e($checkoutCart['global_id']) ?>">
                <div class="col-md-12 d-flex align-items-end gap-2">
                    <button class="btn btn-success">Confirmar compra</button>
                    <a href="carritos.php" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<div class="card card-shadow border-0 mb-4">
    <div class="card-body">
        <form method="post" class="row g-3">
            <input type="hidden" name="id" value="<?= e($editing['global_id'] ?? '') ?>">
            <div class="row g-3 mb-3">
                <?php if (isAdmin()): ?>
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
                <?php else: ?>
                    <div class="col-md-4">
                        <label class="form-label">Cliente</label>
                        <input type="text" class="form-control" value="<?= e($currentClient['nombre']) ?>" disabled>
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
                <?php endif; ?>
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

<template id="carrito-template">
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
</template>

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
<script>
async function loadSucursalStock(sucursalId) {
    const panel = document.getElementById('stock-panel');
    const badge = document.getElementById('stock-updated-badge');

    if (!panel) {
        return;
    }

    if (!sucursalId) {
        panel.innerHTML = '<div class="alert alert-secondary mb-0">Todavia no has seleccionado una sucursal para consultar stock.</div>';
        if (badge) {
            badge.remove();
        }
        return;
    }

    panel.innerHTML = '<div class="alert alert-info mb-0">Consultando stock disponible...</div>';

    try {
        const response = await fetch(`api/stock_disponible.php?id_sucursal=${encodeURIComponent(sucursalId)}`);
        const data = await response.json();

        if (!data.ok) {
            panel.innerHTML = `<div class="alert alert-danger mb-0">${data.message}</div>`;
            return;
        }

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

        const label = badge || (() => {
            const created = document.createElement('span');
            created.id = 'stock-updated-badge';
            created.className = 'badge text-bg-primary';
            document.querySelector('#stock-panel')?.closest('.card-body')?.querySelector('.d-flex')?.appendChild(created);
            return created;
        })();
        label.textContent = `Actualizado: ${data.updated_at}`;
    } catch (error) {
        panel.innerHTML = '<div class="alert alert-danger mb-0">No fue posible consultar el stock disponible.</div>';
    }
}

document.getElementById('stock-sucursal-selector')?.addEventListener('change', function (event) {
    const sucursalId = event.target.value;
    loadSucursalStock(sucursalId);

    const formSucursal = document.querySelector('[data-form-sucursal]');
    if (formSucursal && !formSucursal.value) {
        formSucursal.value = sucursalId;
    }
});

document.querySelectorAll('[data-form-sucursal]').forEach(function (select) {
    select.addEventListener('change', function (event) {
        const sucursalId = event.target.value;
        const stockSelector = document.getElementById('stock-sucursal-selector');
        if (stockSelector) {
            stockSelector.value = sucursalId;
        }
        loadSucursalStock(sucursalId);
    });
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
