<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function isLoggedIn(): bool
{
    return currentUser() !== null;
}

function currentUserRole(): ?string
{
    return currentUser()['rol'] ?? null;
}

function isAdmin(): bool
{
    return currentUserRole() === 'ADMIN';
}

function isClientRole(): bool
{
    return currentUserRole() === 'CLIENTE';
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        setFlash('warning', 'Debe iniciar sesión para continuar.');
        redirect('login.php');
    }
}

function requireRole(string $role): void
{
    requireLogin();

    if ((currentUser()['rol'] ?? '') !== $role) {
        setFlash('danger', 'No tiene permisos para acceder a este módulo.');
        redirect('dashboard.php');
    }
}

function loginUser(array $user): void
{
    $_SESSION['user'] = [
        'id_usuario' => $user['id_usuario'],
        'username' => $user['username'],
        'rol' => $user['rol'],
    ];
}

function logoutUser(): void
{
    unset($_SESSION['user']);
    session_regenerate_id(true);
}
