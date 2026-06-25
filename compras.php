<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/models/Compra.php';
require_once __DIR__ . '/models/Proveedor.php';
require_once __DIR__ . '/models/Producto.php';
require_once __DIR__ . '/models/Sucursal.php';

requireLogin();
requireRole('ADMIN');

$model = new Compra();
$proveedorModel = new Proveedor();
$productoModel = new Producto();
$sucursalModel = new Sucursal();
$errors = [];
$editing = isset($_GET['edit']) ? $model->find((string) $_GET['edit']) : null;

if (isset($_GET['delete'])) {
    try {
        $model->delete((string) $_GET['delete']);
        setFlash('success', 'Compra eliminada y stock revertido.');
    } catch (Throwable $e) {
        setFlash('danger', $e->getMessage());
    }
    redirect('compras.php');
}

if (isPost()) {
    $errors = requireFields($_POST, [
        'id_proveedor' => 'proveedor',
        'id_sucursal' => 'sucursal',
        'fecha' => 'fecha',
    ]);
    $items = normalizeItems($_POST['id_producto'] ?? [], $_POST['cantidad'] ?? [], $_POST['precio'] ?? []);
    if (!$items) {
        $errors[] = 'Debe ingresar al menos un detalle de compra.';
    }

    if (!$errors) {
        try {
            $payload = [
                'id_proveedor' => (int) $_POST['id_proveedor'],
                'id_sucursal' => (int) $_POST['id_sucursal'],
                'fecha' => str_replace('T', ' ', $_POST['fecha']),
                'items' => $items,
            ];
            empty($_POST['id']) ? $model->create($payload) : $model->update((string) $_POST['id'], $payload);
            setFlash('success', 'Compra guardada correctamente.');
            redirect('compras.php');
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
}

$currentModule = 'compras';
$proveedores = $proveedorModel->all();
$productos = $productoModel->active();
$sucursales = $sucursalModel->all();
require __DIR__ . '/includes/header.php';
?>
<?php require __DIR__ . '/includes/flash.php'; ?>
<div class="page-title"><h1 class="h3 mb-0">CRUD Compras Locales por Sucursal</h1><a href="dashboard.php" class="btn btn-outline-secondary">Volver</a></div>
<?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endforeach; ?>
<div class="card card-shadow border-0 mb-4">
    <div class="card-body">
        <form method="post">
            <input type="hidden" name="id" value="<?= e($editing['global_id'] ?? '') ?>">
            <div class="row g-3 mb-3">
                <div class="col-md-4"><label class="form-label">Proveedor</label><select name="id_proveedor" class="form-select"><option value="">Seleccione</option><?php $prov = (string) ($editing['id_proveedor'] ?? old('id_proveedor')); foreach ($proveedores as $item): ?><option value="<?= $item['id_proveedor'] ?>" <?= $prov === (string) $item['id_proveedor'] ? 'selected' : '' ?>><?= e($item['nombre']) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-4"><label class="form-label">Sucursal destino</label><select name="id_sucursal" class="form-select"><option value="">Seleccione</option><?php $suc = (string) ($editing['id_sucursal'] ?? old('id_sucursal')); foreach ($sucursales as $item): ?><option value="<?= $item['id_sucursal'] ?>" <?= $suc === (string) $item['id_sucursal'] ? 'selected' : '' ?>><?= e($item['nombre']) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-4"><label class="form-label">Fecha</label><input type="datetime-local" name="fecha" class="form-control" value="<?= e(str_replace(' ', 'T', substr((string) ($editing['fecha'] ?? old('fecha', date('Y-m-d\TH:i'))), 0, 16))) ?>"></div>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="h5 mb-0">Detalle de compra</h2>
                <button type="button" class="btn btn-outline-primary btn-sm" data-add-row="#compra-items" data-template="#compra-template">Agregar fila</button>
            </div>
            <div id="compra-items">
                <?php $detalle = $editing['detalle'] ?? [['id_producto' => '', 'cantidad' => '', 'costo' => '']]; foreach ($detalle as $row): ?>
                    <div class="row g-2 dynamic-row mb-2">
                        <div class="col-md-5"><select name="id_producto[]" class="form-select"><option value="">Producto</option><?php foreach ($productos as $producto): ?><option value="<?= $producto['id_producto'] ?>" <?= (string) $row['id_producto'] === (string) $producto['id_producto'] ? 'selected' : '' ?>><?= e($producto['producto']) ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-3"><input type="number" name="cantidad[]" class="form-control" placeholder="Cantidad" value="<?= e((string) ($row['cantidad'] ?? '')) ?>"></div>
                        <div class="col-md-3"><input type="number" step="0.01" name="precio[]" class="form-control" placeholder="Costo" value="<?= e((string) ($row['costo'] ?? '')) ?>"></div>
                        <div class="col-md-1"><button type="button" class="btn btn-outline-danger w-100" data-remove-row>x</button></div>
                    </div>
                <?php endforeach; ?>
            </div>
            <button class="btn btn-primary mt-3"><?= $editing ? 'Actualizar' : 'Crear' ?></button>
        </form>
    </div>
</div>
<template id="compra-template">
    <div class="row g-2 dynamic-row mb-2">
        <div class="col-md-5"><select name="id_producto[]" class="form-select"><option value="">Producto</option><?php foreach ($productos as $producto): ?><option value="<?= $producto['id_producto'] ?>"><?= e($producto['producto']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-3"><input type="number" name="cantidad[]" class="form-control" placeholder="Cantidad"></div>
        <div class="col-md-3"><input type="number" step="0.01" name="precio[]" class="form-control" placeholder="Costo"></div>
        <div class="col-md-1"><button type="button" class="btn btn-outline-danger w-100" data-remove-row>x</button></div>
    </div>
</template>
<div class="card card-shadow border-0"><div class="card-body table-responsive">
    <table class="table table-striped"><thead><tr><th>ID Distribuido</th><th>Proveedor</th><th>Sucursal</th><th>Fecha</th><th>Acciones</th></tr></thead><tbody>
    <?php foreach ($model->all() as $item): ?>
        <tr>
            <td><?= e($item['global_id']) ?></td>
            <td><?= e($item['proveedor']) ?></td>
            <td><?= e($item['sucursal']) ?></td>
            <td><?= e($item['fecha']) ?></td>
            <td><a href="compras.php?edit=<?= urlencode((string) $item['global_id']) ?>" class="btn btn-sm btn-warning">Editar</a> <a href="compras.php?delete=<?= urlencode((string) $item['global_id']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Eliminar compra?')">Eliminar</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody></table>
</div></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
