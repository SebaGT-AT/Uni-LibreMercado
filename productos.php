<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/models/Producto.php';

requireLogin();
requireRole('ADMIN');

$model = new Producto();
$errors = [];
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : null;
$editing = $editId ? $model->find($editId) : null;

if ($editId && !$editing) {
    setFlash('warning', 'El producto que intenta editar no existe.');
    redirect('productos.php');
}

if (isset($_GET['delete'])) {
    try {
        $model->delete((int) $_GET['delete']);
        setFlash('success', 'Producto eliminado logicamente.');
    } catch (Throwable $e) {
        setFlash('danger', 'No fue posible eliminar el producto.');
    }
    redirect('productos.php');
}

if (isPost()) {
    $errors = requireFields($_POST, [
        'producto' => 'producto',
        'precio' => 'precio',
    ]);

    if ((float) ($_POST['precio'] ?? 0) <= 0) {
        $errors[] = 'El precio debe ser mayor a cero.';
    }

    if (!$errors) {
        try {
            isset($_POST['id']) && $_POST['id'] !== ''
                ? $model->update((int) $_POST['id'], $_POST)
                : $model->create($_POST);

            setFlash('success', 'Producto guardado correctamente.');
            redirect('productos.php');
        } catch (Throwable $e) {
            $errors[] = 'No fue posible guardar el producto. Revise los datos e intente nuevamente.';
        }
    }
}

$currentModule = 'productos';
require __DIR__ . '/includes/header.php';
?>
<?php require __DIR__ . '/includes/flash.php'; ?>
<div class="page-title">
    <h1 class="h3 mb-0">CRUD Productos</h1>
    <a href="dashboard.php" class="btn btn-outline-secondary">Volver</a>
</div>
<?php foreach ($errors as $error): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endforeach; ?>
<div class="card card-shadow border-0 mb-4">
    <div class="card-body">
        <form method="post" action="<?= $editing ? 'productos.php?edit=' . (int) $editing['id_producto'] : 'productos.php' ?>" class="row g-3">
            <input type="hidden" name="id" value="<?= e($editing['id_producto'] ?? '') ?>">
            <div class="col-md-4">
                <label class="form-label">Producto</label>
                <input type="text" name="producto" class="form-control" value="<?= e((string) old('producto', $editing['producto'] ?? '')) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Precio</label>
                <input type="number" step="0.01" name="precio" class="form-control" value="<?= e((string) old('precio', $editing['precio'] ?? '')) ?>">
            </div>
            <div class="col-md-5">
                <label class="form-label">Descripcion</label>
                <input type="text" name="descripcion" class="form-control" value="<?= e((string) old('descripcion', $editing['descripcion'] ?? '')) ?>">
            </div>
            <div class="col-12">
                <button class="btn btn-primary"><?= $editing ? 'Actualizar' : 'Crear' ?></button>
                <a href="productos.php" class="btn btn-outline-secondary">Limpiar</a>
            </div>
        </form>
    </div>
</div>
<div class="card card-shadow border-0">
    <div class="card-body table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Producto</th>
                    <th>Precio</th>
                    <th>Activo</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($model->all() as $item): ?>
                <tr>
                    <td><?= e((string) $item['id_producto']) ?></td>
                    <td><?= e($item['producto']) ?></td>
                    <td>$<?= number_format((float) $item['precio'], 0, ',', '.') ?></td>
                    <td><?= (int) $item['activo'] === 1 ? 'Si' : 'No' ?></td>
                    <td>
                        <a href="productos.php?edit=<?= $item['id_producto'] ?>" class="btn btn-sm btn-warning">Editar</a>
                        <a href="productos.php?delete=<?= $item['id_producto'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Eliminar producto?')">Eliminar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
