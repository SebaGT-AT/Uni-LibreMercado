<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/models/Nodo.php';

requireRole('ADMIN');

$currentModule = 'nodos';
$model = new Nodo();
$nodes = $model->all();

require __DIR__ . '/includes/header.php';
?>
<?php require __DIR__ . '/includes/flash.php'; ?>
<div class="page-title">
    <h1 class="h3 mb-0">Simulacion de Nodos</h1>
    <a href="dashboard.php" class="btn btn-outline-secondary">Volver</a>
</div>

<div class="alert alert-info">
    Usa este modulo para demostrar la tercera evaluacion. Cuando una sucursal queda en <strong>OFFLINE</strong>, el sistema bloquea compras nuevas en ese nodo para preservar consistencia CP.
</div>

<div id="node-alert"></div>

<div class="card card-shadow border-0">
    <div class="card-body table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Sucursal</th>
                    <th>Estado logico</th>
                    <th>Conectividad actual</th>
                    <th>Ultimo evento</th>
                    <th>Mensaje</th>
                    <th>Accion</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($nodes as $node): ?>
                    <?php $online = (int) $node['online'] === 1; ?>
                    <tr data-node-row="<?= (int) $node['id_sucursal'] ?>">
                        <td><?= e($node['nombre']) ?></td>
                        <td>
                            <span class="badge <?= $online ? 'text-bg-success' : 'text-bg-danger' ?>" data-node-status>
                                <?= $online ? 'ONLINE' : 'OFFLINE' ?>
                            </span>
                        </td>
                        <td>
                            <?php $reachable = $model->actualConnectivity((int) $node['id_sucursal']); ?>
                            <span class="badge <?= $reachable ? 'text-bg-primary' : 'text-bg-secondary' ?>">
                                <?= $reachable ? 'RESPONDE' : 'SIN RESPUESTA' ?>
                            </span>
                        </td>
                        <td data-node-date><?= e((string) ($node['updated_at'] ?? '')) ?></td>
                        <td data-node-message><?= e((string) ($node['status_message'] ?? '')) ?></td>
                        <td>
                            <button
                                type="button"
                                class="btn btn-sm <?= $online ? 'btn-outline-danger' : 'btn-outline-success' ?>"
                                data-toggle-node
                                data-id-sucursal="<?= (int) $node['id_sucursal'] ?>"
                                data-next-state="<?= $online ? '0' : '1' ?>"
                            >
                                <?= $online ? 'Simular falla' : 'Recuperar nodo' ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('click', async function (event) {
    const trigger = event.target.closest('[data-toggle-node]');
    if (!trigger) {
        return;
    }

    const idSucursal = trigger.dataset.idSucursal;
    const nextState = trigger.dataset.nextState;
    const row = document.querySelector(`[data-node-row="${idSucursal}"]`);
    const alertBox = document.getElementById('node-alert');
    const params = new URLSearchParams();

    params.set('id_sucursal', idSucursal);
    params.set('online', nextState);
    params.set('message', nextState === '1'
        ? 'Nodo recuperado desde panel de simulacion.'
        : 'Falla simulada desde panel de administracion.');

    trigger.disabled = true;

    try {
        const response = await fetch('api/node_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
            },
            body: params.toString(),
        });

        const data = await response.json();

        alertBox.innerHTML = `<div class="alert alert-${data.ok ? 'success' : 'danger'}">${data.message}</div>`;

        if (!data.ok || !row || !data.node) {
            return;
        }

        const online = String(data.node.online) === '1';
        const status = row.querySelector('[data-node-status]');
        const date = row.querySelector('[data-node-date]');
        const message = row.querySelector('[data-node-message]');

        status.textContent = online ? 'ONLINE' : 'OFFLINE';
        status.className = `badge ${online ? 'text-bg-success' : 'text-bg-danger'}`;
        date.textContent = data.node.updated_at ?? '';
        message.textContent = data.node.status_message ?? '';

        trigger.textContent = online ? 'Simular falla' : 'Recuperar nodo';
        trigger.className = `btn btn-sm ${online ? 'btn-outline-danger' : 'btn-outline-success'}`;
        trigger.dataset.nextState = online ? '0' : '1';
    } catch (error) {
        alertBox.innerHTML = '<div class="alert alert-danger">No fue posible actualizar el estado del nodo.</div>';
    } finally {
        trigger.disabled = false;
    }
});
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
