<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/models/Proveedor.php';

requireLogin();
requireRole('ADMIN');

$model = new Proveedor();
$errors = [];
$editing = isset($_GET['edit']) ? $model->find((int) $_GET['edit']) : null;

if (isset($_GET['delete'])) {
    $model->delete((int) $_GET['delete']);
    setFlash('success', 'Proveedor eliminado correctamente.');
    redirect('proveedores.php');
}

if (isPost()) {
    $errors = requireFields($_POST, [
        'nombre' => 'nombre',
        'telefono' => 'telefono',
    ]);

    if (!$errors) {
        empty($_POST['id']) ? $model->create($_POST) : $model->update((int) $_POST['id'], $_POST);
        setFlash('success', 'Proveedor guardado correctamente.');
        redirect('proveedores.php');
    }
}

$currentModule = 'proveedores';
require __DIR__ . '/includes/header.php';
?>
<?php require __DIR__ . '/includes/flash.php'; ?>
<div class="page-title"><h1 class="h3 mb-0">CRUD Proveedores</h1><a href="dashboard.php" class="btn btn-outline-secondary">Volver</a></div>
<?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endforeach; ?>
<div class="card card-shadow border-0 mb-4"><div class="card-body">
    <form method="post" class="row g-3">
        <input type="hidden" name="id" value="<?= e($editing['id_proveedor'] ?? '') ?>">
        <div class="col-md-6"><label class="form-label">Nombre</label><input type="text" name="nombre" class="form-control" value="<?= e($editing['nombre'] ?? old('nombre')) ?>"></div>
        <div class="col-md-4"><label class="form-label">Teléfono</label><input type="text" name="telefono" class="form-control" value="<?= e($editing['telefono'] ?? old('telefono')) ?>"></div>
        <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100"><?= $editing ? 'Actualizar' : 'Crear' ?></button></div>
    </form>
</div></div>
<div class="card card-shadow border-0"><div class="card-body table-responsive">
    <table class="table table-striped"><thead><tr><th>ID</th><th>Nombre</th><th>Teléfono</th><th>Acciones</th></tr></thead><tbody>
    <?php foreach ($model->all() as $item): ?>
        <tr>
            <td><?= $item['id_proveedor'] ?></td>
            <td><?= e($item['nombre']) ?></td>
            <td><?= e($item['telefono']) ?></td>
            <td><a href="proveedores.php?edit=<?= $item['id_proveedor'] ?>" class="btn btn-sm btn-warning">Editar</a> <a href="proveedores.php?delete=<?= $item['id_proveedor'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar proveedor?')">Eliminar</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody></table>
</div></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
