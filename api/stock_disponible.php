<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/Stock.php';
require_once __DIR__ . '/../models/Sucursal.php';

requireLogin();

$sucursalId = (int) ($_GET['id_sucursal'] ?? 0);

if ($sucursalId <= 0) {
    jsonResponse([
        'ok' => false,
        'message' => 'Debe indicar una sucursal valida.',
    ], 422);
}

try {
    $stockModel = new Stock();
    $sucursalModel = new Sucursal();
    $sucursal = $sucursalModel->find($sucursalId);

    if (!$sucursal) {
        jsonResponse([
            'ok' => false,
            'message' => 'La sucursal solicitada no existe.',
        ], 404);
    }

    jsonResponse([
        'ok' => true,
        'sucursal' => $sucursal,
        'items' => $stockModel->availabilityBySucursal($sucursalId),
        'updated_at' => date('Y-m-d H:i:s'),
    ]);
} catch (Throwable $e) {
    jsonResponse([
        'ok' => false,
        'message' => $e->getMessage(),
    ], 503);
}
