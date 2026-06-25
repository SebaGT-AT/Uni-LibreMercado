<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/Nodo.php';

requireRole('ADMIN');

if (!isPost()) {
    jsonResponse([
        'ok' => false,
        'message' => 'Metodo no permitido.',
    ], 405);
}

$sucursalId = (int) ($_POST['id_sucursal'] ?? 0);
$online = (int) ($_POST['online'] ?? -1);
$message = trim((string) ($_POST['message'] ?? ''));

if ($sucursalId <= 0 || ($online !== 0 && $online !== 1)) {
    jsonResponse([
        'ok' => false,
        'message' => 'Parametros invalidos para actualizar el nodo.',
    ], 422);
}

try {
    $model = new Nodo();
    $model->setStatus($sucursalId, $online === 1, $message);
    $node = $model->find($sucursalId);

    jsonResponse([
        'ok' => true,
        'message' => $online === 1 ? 'Nodo recuperado correctamente.' : 'Nodo marcado como OFFLINE.',
        'node' => $node,
    ]);
} catch (Throwable $e) {
    jsonResponse([
        'ok' => false,
        'message' => $e->getMessage(),
    ], 500);
}
