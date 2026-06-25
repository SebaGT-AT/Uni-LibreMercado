<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

function redirect(string $url): void
{
    header("Location: {$url}");
    exit;
}

function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function old(string $key, mixed $default = ''): mixed
{
    return $_POST[$key] ?? $default;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function isPost(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function requireFields(array $data, array $fields): array
{
    $errors = [];

    foreach ($fields as $field => $label) {
        if (!isset($data[$field]) || trim((string) $data[$field]) === '') {
            $errors[] = "El campo {$label} es obligatorio.";
        }
    }

    return $errors;
}

function normalizeItems(array $productoIds, array $cantidades, array $precios = []): array
{
    $items = [];

    foreach ($productoIds as $index => $productoId) {
        $productoId = (int) $productoId;
        $cantidad = (int) ($cantidades[$index] ?? 0);
        $precio = isset($precios[$index]) ? (float) $precios[$index] : null;

        if ($productoId > 0 && $cantidad > 0) {
            $items[] = [
                'id_producto' => $productoId,
                'cantidad' => $cantidad,
                'precio' => $precio,
            ];
        }
    }

    return $items;
}

function jsonResponse(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
