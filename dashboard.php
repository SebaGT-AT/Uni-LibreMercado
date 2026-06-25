<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/models/Producto.php';
require_once __DIR__ . '/models/Cliente.php';
require_once __DIR__ . '/models/Venta.php';
require_once __DIR__ . '/models/Compra.php';
require_once __DIR__ . '/models/Carrito.php';
require_once __DIR__ . '/models/Stock.php';
require_once __DIR__ . '/models/Sucursal.php';

requireLogin();

$productoModel = new Producto();
$clienteModel = new Cliente();
$ventaModel = new Venta();
$compraModel = new Compra();
$carritoModel = new Carrito();
$stockModel = new Stock();
$sucursalModel = new Sucursal();
$currentClient = null;

if (isClientRole()) {
    $currentClient = $clienteModel->findByUsuarioId((int) currentUser()['id_usuario']);
}

$currentModule = 'dashboard';
require_once __DIR__ . '/includes/header.php';
?>
<?php require __DIR__ . '/includes/flash.php'; ?>
<div class="page-title">
    <div>
        <h1 class="h3 mb-1">Dashboard distribuido</h1>
        <p class="text-muted mb-0">
            <?= isAdmin() ? 'Vista general de los tres nodos del sistema.' : 'Panel de cliente para preparar y confirmar tus compras.' ?>
        </p>
    </div>
</div>

<?php if (isAdmin()): ?>
    <div class="row g-4">
        <div class="col-md-3">
            <div class="card card-shadow border-0">
                <div class="card-body">
                    <h2 class="h6 text-muted">Productos activos</h2>
                    <div class="display-6"><?= count(array_filter($productoModel->all(), fn($item) => (int) $item['activo'] === 1)) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-shadow border-0">
                <div class="card-body">
                    <h2 class="h6 text-muted">Clientes</h2>
                    <div class="display-6"><?= count($clienteModel->all()) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-shadow border-0">
                <div class="card-body">
                    <h2 class="h6 text-muted">Ventas</h2>
                    <div class="display-6"><?= count($ventaModel->all()) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-shadow border-0">
                <div class="card-body">
                    <h2 class="h6 text-muted">Compras</h2>
                    <div class="display-6"><?= count($compraModel->all()) ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="row g-4 mt-1">
        <div class="col-lg-6">
            <div class="card card-shadow border-0 h-100">
                <div class="card-body">
                    <h2 class="h5">Arquitectura distribuida</h2>
                    <ul class="mb-0">
                        <li>Nodo central: catalogo, usuarios, clientes, proveedores y ventas.</li>
                        <li>Sucursales: stock, compras y carritos locales por sede.</li>
                        <li>La venta coordina nodo central + nodo de sucursal mediante PDO.</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card card-shadow border-0 h-100">
                <div class="card-body">
                    <h2 class="h5">Criterio CAP elegido</h2>
                    <p class="mb-0">Se prioriza CP: si una sucursal falla, solo se bloquean las operaciones que dependen de esa sucursal. El resto del sistema sigue consultando datos globales sin comprometer la consistencia.</p>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card card-shadow border-0">
                <div class="card-body">
                    <h2 class="h6 text-muted">Mis carritos</h2>
                    <div class="display-6"><?= $currentClient ? count($carritoModel->allByCliente((int) $currentClient['id_cliente'])) : 0 ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-shadow border-0">
                <div class="card-body">
                    <h2 class="h6 text-muted">Mis compras</h2>
                    <div class="display-6"><?= $currentClient ? count($ventaModel->allByCliente((int) $currentClient['id_cliente'])) : 0 ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-shadow border-0">
                <div class="card-body">
                    <h2 class="h6 text-muted">Productos activos</h2>
                    <div class="display-6"><?= count(array_filter($productoModel->all(), fn($item) => (int) $item['activo'] === 1)) ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="row g-4 mt-1">
        <div class="col-lg-6">
            <div class="card card-shadow border-0 h-100">
                <div class="card-body">
                    <h2 class="h5">Como comprar</h2>
                    <ol class="mb-0">
                        <li>Entra a Carritos.</li>
                        <li>Agrega productos y cantidades.</li>
                        <li>Presiona Confirmar compra.</li>
                        <li>Selecciona la sucursal desde donde se despachara.</li>
                    </ol>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card card-shadow border-0 h-100">
                <div class="card-body">
                    <h2 class="h5">Que garantiza el sistema</h2>
                    <p class="mb-0">Si no hay stock suficiente o falla un nodo, la compra no se confirma. Esto protege la consistencia del inventario y evita ventas incompletas.</p>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card card-shadow border-0 h-100">
                <div class="card-body">
                    <h2 class="h5">Stock disponible por sucursal</h2>
                    <p class="text-muted">Antes de comprar, puedes revisar existencias actualizadas desde el modulo Carritos.</p>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($sucursalModel->all() as $sucursal): ?>
                            <a href="carritos.php?stock_sucursal=<?= (int) $sucursal['id_sucursal'] ?>" class="btn btn-outline-primary btn-sm">
                                Ver stock <?= e($sucursal['nombre']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
