<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/models/Cliente.php';
require_once __DIR__ . '/models/Usuario.php';

requireLogin();
requireRole('ADMIN');

$model = new Cliente();
$usuarioModel = new Usuario();
$errors = [];
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : null;
$editing = $editId ? $model->find($editId) : null;

if ($editId && !$editing) {
    setFlash('warning', 'El cliente que intenta editar no existe.');
    redirect('clientes.php');
}

if (isset($_GET['delete'])) {
    try {
        $model->delete((int) $_GET['delete']);
        setFlash('success', 'Cliente eliminado correctamente.');
    } catch (Throwable $e) {
        setFlash('danger', 'No fue posible eliminar el cliente.');
    }
    redirect('clientes.php');
}

if (isPost()) {
    $errors = requireFields($_POST, [
        'nombre' => 'nombre',
        'telefono' => 'telefono',
        'id_usuario' => 'usuario',
    ]);

    if (!$errors) {
        try {
            empty($_POST['id']) ? $model->create($_POST) : $model->update((int) $_POST['id'], $_POST);
            setFlash('success', 'Cliente guardado correctamente.');
            redirect('clientes.php');
        } catch (Throwable $e) {
            $errors[] = 'No fue posible guardar el cliente. Verifique que el usuario seleccionado no este asignado a otro cliente.';
        }
    }
}

$currentModule = 'clientes';
$usuarios = $usuarioModel->activeClients(isset($editing['id_usuario']) ? (int) $editing['id_usuario'] : null);
require __DIR__ . '/includes/header.php';
?>
<?php require __DIR__ . '/includes/flash.php'; ?>
<div class="page-title">
    <h1 class="h3 mb-0">CRUD Clientes</h1>
    <a href="dashboard.php" class="btn btn-outline-secondary">Volver</a>
</div>
<?php foreach ($errors as $error): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endforeach; ?>
<div class="card card-shadow border-0 mb-4">
    <div class="card-body">
        <form method="post" action="<?= $editing ? 'clientes.php?edit=' . (int) $editing['id_cliente'] : 'clientes.php' ?>" class="row g-3">
            <input type="hidden" name="id" value="<?= e($editing['id_cliente'] ?? '') ?>">
            <div class="col-md-4">
                <label class="form-label">Nombre</label>
                <input type="text" name="nombre" class="form-control" value="<?= e((string) old('nombre', $editing['nombre'] ?? '')) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Telefono</label>
                <input type="text" name="telefono" class="form-control" value="<?= e((string) old('telefono', $editing['telefono'] ?? '')) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Usuario cliente</label>
                <?php $usuarioActual = (string) old('id_usuario', $editing['id_usuario'] ?? ''); ?>
                <select name="id_usuario" class="form-select">
                    <option value="">Seleccione</option>
                    <?php foreach ($usuarios as $usuario): ?>
                        <option value="<?= $usuario['id_usuario'] ?>" <?= $usuarioActual === (string) $usuario['id_usuario'] ? 'selected' : '' ?>><?= e($usuario['username']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <button class="btn btn-primary"><?= $editing ? 'Actualizar' : 'Crear' ?></button>
                <a href="clientes.php" class="btn btn-outline-secondary">Limpiar</a>
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
                    <th>Nombre</th>
                    <th>Telefono</th>
                    <th>Usuario</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($model->all() as $item): ?>
                <tr>
                    <td><?= e((string) $item['id_cliente']) ?></td>
                    <td><?= e($item['nombre']) ?></td>
                    <td><?= e($item['telefono']) ?></td>
                    <td><?= e($item['username']) ?></td>
                    <td>
                        <a href="clientes.php?edit=<?= $item['id_cliente'] ?>" class="btn btn-sm btn-warning">Editar</a>
                        <a href="clientes.php?delete=<?= $item['id_cliente'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Eliminar cliente?')">Eliminar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
