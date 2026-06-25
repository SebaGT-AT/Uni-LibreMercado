# Libre Mercado

Sistema de comercio electronico distribuido desarrollado en PHP puro, PDO y MySQL para una evaluacion de Sistemas Distribuidos.

## Estado del proyecto
- CRUD completo para productos, usuarios, clientes, sucursales, stock, proveedores, compras, carritos y ventas.
- Arquitectura distribuida refactorizada a un nodo central y tres nodos de sucursal.
- Login con sesiones, `password_hash()` y `password_verify()`.
- Bootstrap 5, navbar, mensajes flash, validaciones y botones volver.
- Transacciones ACID en ventas con coordinacion entre nodo central y nodo de sucursal.
- Simulacion de Two Phase Commit simplificado.
- Eleccion CAP de tipo CP documentada.
- Procedimientos almacenados para compra, ajuste de stock, recuperacion y cambio de estado de nodos.
- Panel de simulacion de nodos y compra distribuida usando PHP + AJAX.
- Soporte de ejecucion con XAMPP y Docker.

## Nodos distribuidos
- Nodo central: `libre_mercado_central`
  - `productos`
  - `sucursales`
  - `usuarios`
  - `clientes`
  - `proveedores`
  - `ventas`
  - `detalle_venta`
  - `distributed_transactions`
- Nodo sucursal norte: `libre_mercado_norte`
  - `stock`
  - `compras`
  - `detalle_compras`
  - `carrito`
  - `detalle_carrito`
- Nodo sucursal centro: `libre_mercado_centro`
  - mismas tablas locales de operacion
- Nodo sucursal sur: `libre_mercado_sur`
  - mismas tablas locales de operacion
  - `stock_movimientos`

## Funcionalidades de la tercera evaluacion
- `sp_realizar_compra()`: valida stock y descuenta inventario en la sucursal.
- `sp_actualizar_stock()`: ajusta existencias de forma controlada.
- `sp_reconstruir_stock()`: recompone el stock de una transaccion abortada.
- `sp_set_node_status()`: permite marcar una sucursal como `ONLINE` u `OFFLINE`.
- [nodos.php](C:/Users/sebag/Documents/Libre Mercado/nodos.php): panel para simular caida y recuperacion de nodos.
- [api/checkout.php](C:/Users/sebag/Documents/Libre Mercado/api/checkout.php): compra distribuida con respuesta JSON.
- [api/node_status.php](C:/Users/sebag/Documents/Libre Mercado/api/node_status.php): cambio de estado de nodos por AJAX.

## Logica de la arquitectura
- Los datos globales viven en el nodo central.
- El stock, los carritos y las compras viven en el nodo de cada sucursal.
- Una venta escribe en dos nodos:
  - crea la venta en el nodo central
  - descuenta stock en el nodo de la sucursal
- Si uno de los dos falla, se hace rollback total.

## Ejecucion con Docker
1. Ejecutar:
```bash
docker compose up -d --build
```
Si ya habias levantado los contenedores antes de estos cambios y quieres regenerar procedimientos almacenados desde cero:
```bash
docker compose down -v
docker compose up -d --build
```
2. Abrir:
```text
http://localhost:8080/index.php
```
3. Puertos de base de datos:
- Central: `3407`
- Norte: `3408`
- Centro: `3409`
- Sur: `3410`

## Ejecucion con XAMPP
XAMPP queda como modo de desarrollo local en una sola instancia MySQL. El proyecto mantiene la misma logica distribuida, pero las cuatro bases viven dentro del mismo servidor MySQL.

1. Instalar XAMPP.
2. Copiar esta carpeta a `C:\xampp\htdocs\Libre Mercado`.
3. Iniciar `Apache` y `MySQL`.
4. Ejecutar el script [sql/libre_mercado_distribuido.sql](C:/Users/sebag/Documents/Libre Mercado/sql/libre_mercado_distribuido.sql).
5. Configurar, si hace falta, las variables de entorno para que todos los hosts apunten a `127.0.0.1:3306`.

## Cliente de base de datos
Puedes usar:
- phpMyAdmin
- DBeaver
- HeidiSQL
- MySQL Workbench

## Usuarios de prueba
- `admin` / `password`
- `cliente1` / `password`
- `cliente2` / `password`

## CAP
Se eligio CP:
- Se prioriza consistencia e integridad del inventario.
- Si falla una sucursal, la venta de esa sucursal se bloquea.
- El resto del sistema puede seguir consultando datos globales.
- La demostracion de la particion simulada se realiza dejando una sucursal en `OFFLINE` desde [nodos.php](C:/Users/sebag/Documents/Libre Mercado/nodos.php).

## Archivos clave
- [config/db_central.php](C:/Users/sebag/Documents/Libre Mercado/config/db_central.php)
- [config/db_sucursales.php](C:/Users/sebag/Documents/Libre Mercado/config/db_sucursales.php)
- [models/Venta.php](C:/Users/sebag/Documents/Libre Mercado/models/Venta.php)
- [models/Compra.php](C:/Users/sebag/Documents/Libre Mercado/models/Compra.php)
- [models/Carrito.php](C:/Users/sebag/Documents/Libre Mercado/models/Carrito.php)
- [models/Stock.php](C:/Users/sebag/Documents/Libre Mercado/models/Stock.php)
