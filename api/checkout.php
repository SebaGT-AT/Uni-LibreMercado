<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/Carrito.php';
require_once __DIR__ . '/../models/Cliente.php';
require_once __DIR__ . '/../models/Venta.php';

requireLogin();

if (!isPost()) {
    jsonResponse([
        'ok' => false,
        'message' => 'Metodo no permitido.',
    ], 405);
}

$clienteModel = new Cliente();
$carritoModel = new Carrito();
$ventaModel = new Venta();
$currentClient = isClientRole() ? $clienteModel->findByUsuarioId((int) currentUser()['id_usuario']) : null;
$checkoutId = trim((string) ($_POST['checkout_id'] ?? ''));

if ($checkoutId === '') {
    jsonResponse([
        'ok' => false,
        'message' => 'Debe indicar el carrito a procesar.',
    ], 422);
}

if (isClientRole() && !$currentClient) {
    jsonResponse([
        'ok' => false,
        'message' => 'Tu usuario no tiene un cliente asociado.',
    ], 403);
}

$cart = isClientRole()
    ? $carritoModel->findForCliente($checkoutId, (int) $currentClient['id_cliente'])
    : $carritoModel->find($checkoutId);

if (!$cart) {
    jsonResponse([
        'ok' => false,
        'message' => 'El carrito seleccionado no existe o no te pertenece.',
    ], 404);
}

try {
    $items = array_map(
        static fn(array $item): array => [
            'id_producto' => (int) $item['id_producto'],
            'cantidad' => (int) $item['cantidad'],
        ],
        $cart['detalle']
    );

    $ventaModel->create([
        'id_cliente' => (int) $cart['id_cliente'],
        'id_sucursal' => (int) $cart['id_sucursal'],
        'fecha' => date('Y-m-d H:i:s'),
        'items' => $items,
    ]);

    if (isClientRole()) {
        $carritoModel->deleteForCliente((string) $cart['global_id'], (int) $currentClient['id_cliente']);
    } else {
        $carritoModel->delete((string) $cart['global_id']);
    }

    jsonResponse([
        'ok' => true,
        'message' => 'Compra distribuida confirmada correctamente.',
        'redirect' => 'carritos.php?stock_sucursal=' . (int) $cart['id_sucursal'],
    ]);
} catch (Throwable $e) {
    jsonResponse([
        'ok' => false,
        'message' => $e->getMessage(),
    ], 422);
}
