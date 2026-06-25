<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/models/Usuario.php';

requireLogin();
requireRole('ADMIN');

$model = new Usuario();
$errors = [];
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : null;
$editing = $editId ? $model->find($editId) : null;

if ($editId && !$editing) {
    setFlash('warning', 'El usuario que intenta editar no existe.');
    redirect('usuarios.php');
}

if (isset($_GET['delete'])) {
    try {
        $model->delete((int) $_GET['delete']);
        setFlash('success', 'Usuario desactivado correctamente.');
    } catch (Throwable $e) {
        setFlash('danger', 'No fue posible eliminar el usuario.');
    }
    redirect('usuarios.php');
}

if (isPost()) {
    $required = [
        'username' => 'usuario',
        'rol' => 'rol',
    ];

    if (empty($_POST['id'])) {
        $required['password'] = 'contrasena';
    }

    $errors = requireFields($_POST, $required);

    if (!$errors) {
        try {
            empty($_POST['id']) ? $model->create($_POST) : $model->update((int) $_POST['id'], $_POST);
            setFlash('success', 'Usuario guardado correctamente.');
            redirect('usuarios.php');
        } catch (Throwable $e) {
            $errors[] = 'No fue posible guardar el usuario. Verifique que el username no este repetido.';
        }
    }
}

$currentModule = 'usuarios';
require __DIR__ . '/includes/header.php';
?>
<?php require __DIR__ . '/includes/flash.php'; ?>
<div class="page-title">
    <h1 class="h3 mb-0">CRUD Usuarios</h1>
    <a href="dashboard.php" class="btn btn-outline-secondary">Volver</a>
</div>
<?php foreach ($errors as $error): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endforeach; ?>
<div class="card card-shadow border-0 mb-4">
    <div class="card-body">
        <form method="post" action="<?= $editing ? 'usuarios.php?edit=' . (int) $editing['id_usuario'] : 'usuarios.php' ?>" class="row g-3">
            <input type="hidden" name="id" value="<?= e($editing['id_usuario'] ?? '') ?>">
            <div class="col-md-4">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" value="<?= e((string) old('username', $editing['username'] ?? '')) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Contrasena</label>
                <input type="password" name="password" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label">Rol</label>
                <?php $rolActual = (string) old('rol', $editing['rol'] ?? 'CLIENTE'); ?>
                <select name="rol" class="form-select">
                    <option value="ADMIN" <?= $rolActual === 'ADMIN' ? 'selected' : '' ?>>ADMIN</option>
                    <option value="CLIENTE" <?= $rolActual === 'CLIENTE' ? 'selected' : '' ?>>CLIENTE</option>
                </select>
            </div>
            <div class="col-12">
                <button class="btn btn-primary"><?= $editing ? 'Actualizar' : 'Crear' ?></button>
                <a href="usuarios.php" class="btn btn-outline-secondary">Limpiar</a>
            </div>
        </form>
    </div>
</div>
<div class="card card-shadow border-0">
    <div class="card-body table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Usuario</th>
                    <th>Rol</th>
                    <th>Activo</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($model->all() as $item): ?>
                <tr>
                    <td><?= e((string) $item['id_usuario']) ?></td>
                    <td><?= e($item['username']) ?></td>
                    <td><?= e($item['rol']) ?></td>
                    <td><?= (int) $item['activo'] === 1 ? 'Si' : 'No' ?></td>
                    <td>
                        <a href="usuarios.php?edit=<?= $item['id_usuario'] ?>" class="btn btn-sm btn-warning">Editar</a>
                        <a href="usuarios.php?delete=<?= $item['id_usuario'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Eliminar usuario?')">Eliminar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
