<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

$user = currentUser();
$currentModule = $currentModule ?? '';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="dashboard.php">Libre Mercado</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNavbar">
            <?php if ($user): ?>
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link <?= $currentModule === 'dashboard' ? 'active' : '' ?>" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link <?= $currentModule === 'carritos' ? 'active' : '' ?>" href="carritos.php">Carritos</a></li>
                    <?php if (isClientRole()): ?>
                        <li class="nav-item"><a class="nav-link <?= $currentModule === 'mis_compras' ? 'active' : '' ?>" href="mis_compras.php">Mis Compras</a></li>
                    <?php endif; ?>
                    <?php if (isAdmin()): ?>
                        <li class="nav-item"><a class="nav-link <?= $currentModule === 'nodos' ? 'active' : '' ?>" href="nodos.php">Nodos</a></li>
                        <li class="nav-item"><a class="nav-link <?= $currentModule === 'productos' ? 'active' : '' ?>" href="productos.php">Productos</a></li>
                        <li class="nav-item"><a class="nav-link <?= $currentModule === 'clientes' ? 'active' : '' ?>" href="clientes.php">Clientes</a></li>
                        <li class="nav-item"><a class="nav-link <?= $currentModule === 'usuarios' ? 'active' : '' ?>" href="usuarios.php">Usuarios</a></li>
                        <li class="nav-item"><a class="nav-link <?= $currentModule === 'sucursales' ? 'active' : '' ?>" href="sucursales.php">Sucursales</a></li>
                        <li class="nav-item"><a class="nav-link <?= $currentModule === 'stock' ? 'active' : '' ?>" href="stock.php">Stock</a></li>
                        <li class="nav-item"><a class="nav-link <?= $currentModule === 'proveedores' ? 'active' : '' ?>" href="proveedores.php">Proveedores</a></li>
                        <li class="nav-item"><a class="nav-link <?= $currentModule === 'compras' ? 'active' : '' ?>" href="compras.php">Compras</a></li>
                        <li class="nav-item"><a class="nav-link <?= $currentModule === 'ventas' ? 'active' : '' ?>" href="ventas.php">Ventas</a></li>
                    <?php endif; ?>
                </ul>
                <div class="d-flex align-items-center gap-3 text-white">
                    <span><?= e($user['username']) ?> (<?= e($user['rol']) ?>)</span>
                    <a class="btn btn-outline-light btn-sm" href="logout.php">Salir</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>
<main class="container py-4">
