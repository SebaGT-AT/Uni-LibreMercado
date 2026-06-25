<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/models/Sucursal.php';

requireLogin();
requireRole('ADMIN');

$model = new Sucursal();
$errors = [];
$editing = isset($_GET['edit']) ? $model->find((int) $_GET['edit']) : null;

if (isset($_GET['delete'])) {
    $model->delete((int) $_GET['delete']);
    setFlash('success', 'Sucursal eliminada correctamente.');
    redirect('sucursales.php');
}

if (isPost()) {
    $errors = requireFields($_POST, ['nombre' => 'nombre']);
    if (!$errors) {
        empty($_POST['id']) ? $model->create($_POST) : $model->update((int) $_POST['id'], $_POST);
        setFlash('success', 'Sucursal guardada correctamente.');
        redirect('sucursales.php');
    }
}

$currentModule = 'sucursales';
require __DIR__ . '/includes/header.php';
?>
<?php require __DIR__ . '/includes/flash.php'; ?>
<div class="page-title"><h1 class="h3 mb-0">CRUD Sucursales</h1><a href="dashboard.php" class="btn btn-outline-secondary">Volver</a></div>
<?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endforeach; ?>
<div class="card card-shadow border-0 mb-4"><div class="card-body">
    <form method="post" class="row g-3">
        <input type="hidden" name="id" value="<?= e($editing['id_sucursal'] ?? '') ?>">
        <div class="col-md-8"><label class="form-label">Nombre</label><input type="text" name="nombre" class="form-control" value="<?= e($editing['nombre'] ?? old('nombre')) ?>"></div>
        <div class="col-md-4 d-flex align-items-end gap-2">
            <button class="btn btn-primary"><?= $editing ? 'Actualizar' : 'Crear' ?></button>
            <a href="sucursales.php" class="btn btn-outline-secondary">Limpiar</a>
        </div>
    </form>
</div></div>
<div class="card card-shadow border-0"><div class="card-body table-responsive">
    <table class="table table-striped"><thead><tr><th>ID</th><th>Nombre</th><th>Acciones</th></tr></thead><tbody>
    <?php foreach ($model->all() as $item): ?>
        <tr>
            <td><?= $item['id_sucursal'] ?></td>
            <td><?= e($item['nombre']) ?></td>
            <td>
                <a href="sucursales.php?edit=<?= $item['id_sucursal'] ?>" class="btn btn-sm btn-warning">Editar</a>
                <a href="sucursales.php?delete=<?= $item['id_sucursal'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar sucursal?')">Eliminar</a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody></table>
</div></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
