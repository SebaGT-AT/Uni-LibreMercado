<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/models/Venta.php';
require_once __DIR__ . '/models/Cliente.php';
require_once __DIR__ . '/models/Producto.php';
require_once __DIR__ . '/models/Sucursal.php';

requireLogin();
requireRole('ADMIN');

$model = new Venta();
$clienteModel = new Cliente();
$productoModel = new Producto();
$sucursalModel = new Sucursal();
$errors = [];
$editing = isset($_GET['edit']) ? $model->find((int) $_GET['edit']) : null;

if (isset($_GET['delete'])) {
    try {
        $model->delete((int) $_GET['delete']);
        setFlash('success', 'Venta eliminada y stock restituido.');
    } catch (Throwable $e) {
        setFlash('danger', $e->getMessage());
    }
    redirect('ventas.php');
}

if (isPost()) {
    $errors = requireFields($_POST, [
        'id_cliente' => 'cliente',
        'id_sucursal' => 'sucursal',
        'fecha' => 'fecha',
    ]);

    $items = normalizeItems($_POST['id_producto'] ?? [], $_POST['cantidad'] ?? []);
    if (!$items) {
        $errors[] = 'Debe agregar al menos un producto a la venta.';
    }

    if (!$errors) {
        try {
            $payload = [
                'id_cliente' => (int) $_POST['id_cliente'],
                'id_sucursal' => (int) $_POST['id_sucursal'],
                'fecha' => str_replace('T', ' ', $_POST['fecha']),
                'items' => $items,
            ];
            empty($_POST['id']) ? $model->create($payload) : $model->update((int) $_POST['id'], $payload);
            setFlash('success', 'Venta procesada correctamente.');
            redirect('ventas.php');
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
}

$currentModule = 'ventas';
$clientes = $clienteModel->all();
$productos = $productoModel->active();
$sucursales = $sucursalModel->all();
require __DIR__ . '/includes/header.php';
?>
<?php require __DIR__ . '/includes/flash.php'; ?>
<div class="page-title"><h1 class="h3 mb-0">CRUD Ventas con ACID</h1><a href="dashboard.php" class="btn btn-outline-secondary">Volver</a></div>
<?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endforeach; ?>
<div class="card card-shadow border-0 mb-4"><div class="card-body">
    <form method="post">
        <input type="hidden" name="id" value="<?= e($editing['id_venta'] ?? '') ?>">
        <div class="row g-3 mb-3">
            <div class="col-md-4"><label class="form-label">Cliente</label><select name="id_cliente" class="form-select"><option value="">Seleccione</option><?php $clienteActual = (string) ($editing['id_cliente'] ?? old('id_cliente')); foreach ($clientes as $cliente): ?><option value="<?= $cliente['id_cliente'] ?>" <?= $clienteActual === (string) $cliente['id_cliente'] ? 'selected' : '' ?>><?= e($cliente['nombre']) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-4"><label class="form-label">Sucursal</label><select name="id_sucursal" class="form-select"><option value="">Seleccione</option><?php $sucursalActual = (string) ($editing['id_sucursal'] ?? old('id_sucursal')); foreach ($sucursales as $sucursal): ?><option value="<?= $sucursal['id_sucursal'] ?>" <?= $sucursalActual === (string) $sucursal['id_sucursal'] ? 'selected' : '' ?>><?= e($sucursal['nombre']) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-4"><label class="form-label">Fecha</label><input type="datetime-local" name="fecha" class="form-control" value="<?= e(str_replace(' ', 'T', substr((string) ($editing['fecha'] ?? old('fecha', date('Y-m-d\TH:i'))), 0, 16))) ?>"></div>
        </div>
        <div class="alert alert-info">
            Flujo CP: si falla el nodo de stock o el nodo de ventas, la operacion se aborta para proteger la consistencia.
        </div>
        <div class="d-flex justify-content-between align-items-center mb-2"><h2 class="h5 mb-0">Detalle de venta</h2><button type="button" class="btn btn-outline-primary btn-sm" data-add-row="#venta-items" data-template="#venta-template">Agregar fila</button></div>
        <div id="venta-items">
            <?php $detalle = $editing['detalle'] ?? [['id_producto' => '', 'cantidad' => '']]; foreach ($detalle as $row): ?>
                <div class="row g-2 dynamic-row mb-2">
                    <div class="col-md-7"><select name="id_producto[]" class="form-select"><option value="">Producto</option><?php foreach ($productos as $producto): ?><option value="<?= $producto['id_producto'] ?>" <?= (string) $row['id_producto'] === (string) $producto['id_producto'] ? 'selected' : '' ?>><?= e($producto['producto']) ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-4"><input type="number" name="cantidad[]" class="form-control" placeholder="Cantidad" value="<?= e((string) ($row['cantidad'] ?? '')) ?>"></div>
                    <div class="col-md-1"><button type="button" class="btn btn-outline-danger w-100" data-remove-row>x</button></div>
                </div>
            <?php endforeach; ?>
        </div>
        <button class="btn btn-primary mt-3"><?= $editing ? 'Actualizar' : 'Registrar venta' ?></button>
    </form>
</div></div>
<template id="venta-template">
    <div class="row g-2 dynamic-row mb-2">
        <div class="col-md-7"><select name="id_producto[]" class="form-select"><option value="">Producto</option><?php foreach ($productos as $producto): ?><option value="<?= $producto['id_producto'] ?>"><?= e($producto['producto']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-4"><input type="number" name="cantidad[]" class="form-control" placeholder="Cantidad"></div>
        <div class="col-md-1"><button type="button" class="btn btn-outline-danger w-100" data-remove-row>x</button></div>
    </div>
</template>
<div class="card card-shadow border-0"><div class="card-body table-responsive">
    <table class="table table-striped"><thead><tr><th>ID</th><th>Cliente</th><th>Sucursal</th><th>Fecha</th><th>Total</th><th>Acciones</th></tr></thead><tbody>
    <?php foreach ($model->all() as $item): ?>
        <tr>
            <td><?= $item['id_venta'] ?></td>
            <td><?= e($item['cliente']) ?></td>
            <td><?= e($item['sucursal']) ?></td>
            <td><?= e($item['fecha']) ?></td>
            <td>$<?= number_format((float) $item['total'], 0, ',', '.') ?></td>
            <td><a href="ventas.php?edit=<?= $item['id_venta'] ?>" class="btn btn-sm btn-warning">Editar</a> <a href="ventas.php?delete=<?= $item['id_venta'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Eliminar venta?')">Eliminar</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody></table>
</div></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
