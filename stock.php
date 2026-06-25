<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/models/Stock.php';
require_once __DIR__ . '/models/Producto.php';
require_once __DIR__ . '/models/Sucursal.php';

requireLogin();
requireRole('ADMIN');

$model = new Stock();
$productoModel = new Producto();
$sucursalModel = new Sucursal();
$errors = [];
$editing = isset($_GET['edit']) ? $model->find((string) $_GET['edit']) : null;

if (isset($_GET['delete'])) {
    $model->delete((string) $_GET['delete']);
    setFlash('success', 'Registro de stock eliminado.');
    redirect('stock.php');
}

if (isPost()) {
    $errors = requireFields($_POST, [
        'id_producto' => 'producto',
        'id_sucursal' => 'sucursal',
        'cantidad' => 'cantidad',
    ]);

    if ((int) ($_POST['cantidad'] ?? -1) < 0) {
        $errors[] = 'La cantidad no puede ser negativa.';
    }

    if (!$errors) {
        try {
            empty($_POST['id']) ? $model->create($_POST) : $model->update((string) $_POST['id'], $_POST);
            setFlash('success', 'Stock guardado correctamente.');
            redirect('stock.php');
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
}

$currentModule = 'stock';
$productos = $productoModel->active();
$sucursales = $sucursalModel->all();
require __DIR__ . '/includes/header.php';
?>
<?php require __DIR__ . '/includes/flash.php'; ?>
<div class="page-title"><h1 class="h3 mb-0">CRUD Stock por Sucursal</h1><a href="dashboard.php" class="btn btn-outline-secondary">Volver</a></div>
<?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endforeach; ?>
<div class="card card-shadow border-0 mb-4"><div class="card-body">
    <form method="post" class="row g-3">
        <input type="hidden" name="id" value="<?= e($editing['global_id'] ?? '') ?>">
        <div class="col-md-4"><label class="form-label">Producto</label><select name="id_producto" class="form-select"><option value="">Seleccione</option><?php $p = (string) ($editing['id_producto'] ?? old('id_producto')); foreach ($productos as $producto): ?><option value="<?= $producto['id_producto'] ?>" <?= $p === (string) $producto['id_producto'] ? 'selected' : '' ?>><?= e($producto['producto']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-4"><label class="form-label">Sucursal</label><select name="id_sucursal" class="form-select"><option value="">Seleccione</option><?php $s = (string) ($editing['id_sucursal'] ?? old('id_sucursal')); foreach ($sucursales as $sucursal): ?><option value="<?= $sucursal['id_sucursal'] ?>" <?= $s === (string) $sucursal['id_sucursal'] ? 'selected' : '' ?>><?= e($sucursal['nombre']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-4"><label class="form-label">Cantidad</label><input type="number" name="cantidad" class="form-control" value="<?= e((string) ($editing['cantidad'] ?? old('cantidad'))) ?>"></div>
        <div class="col-12"><button class="btn btn-primary"><?= $editing ? 'Actualizar' : 'Crear' ?></button><a href="stock.php" class="btn btn-outline-secondary">Limpiar</a></div>
    </form>
</div></div>
<div class="card card-shadow border-0"><div class="card-body table-responsive">
    <table class="table table-striped"><thead><tr><th>ID Distribuido</th><th>Producto</th><th>Sucursal</th><th>Cantidad</th><th>Acciones</th></tr></thead><tbody>
    <?php foreach ($model->all() as $item): ?>
        <tr>
            <td><?= e($item['global_id']) ?></td>
            <td><?= e($item['producto']) ?></td>
            <td><?= e($item['sucursal']) ?></td>
            <td><?= $item['cantidad'] ?></td>
            <td><a href="stock.php?edit=<?= urlencode((string) $item['global_id']) ?>" class="btn btn-sm btn-warning">Editar</a> <a href="stock.php?delete=<?= urlencode((string) $item['global_id']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Eliminar stock?')">Eliminar</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody></table>
</div></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
