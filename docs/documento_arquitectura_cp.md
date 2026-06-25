# Documento de Arquitectura Distribuida
## Proyecto: Libre Mercado

### 1. Arquitectura propuesta
Libre Mercado se reorganizo como un sistema distribuido con un nodo central y tres nodos de sucursal. El nodo central almacena catalogo, usuarios, clientes, proveedores, ventas y el registro de transacciones distribuidas. Cada sucursal mantiene sus propios datos operativos: stock, compras y carritos.

Esta particion evita que una caida del nodo de clientes o del catalogo elimine entidades globales del sistema. Si una sucursal deja de responder, la falla queda acotada a esa sucursal.

### 2. Nodos
- Nodo central: productos, sucursales, usuarios, clientes, proveedores, ventas, detalle_venta y distributed_transactions.
- Nodo sucursal norte: stock, compras y carritos locales.
- Nodo sucursal centro: stock, compras y carritos locales.
- Nodo sucursal sur: stock, compras y carritos locales.

### 3. Flujo de venta
1. El usuario arma un carrito asociado a una sucursal.
2. La aplicacion registra la intencion de venta en `distributed_transactions` del nodo central.
3. Se abren transacciones en el nodo central y en el nodo de la sucursal elegida.
4. Se valida y descuenta stock en la sucursal.
5. Se registra la venta y su detalle en el nodo central.
6. Si ambos nodos responden correctamente, se hace `COMMIT`.
7. Si uno falla, se hace `ROLLBACK` en ambos.

### 4. Eleccion CAP: CP
Se privilegia CP porque en comercio electronico no se puede aceptar una venta si la sucursal no confirma el inventario. Si una particion de red impide validar o descontar stock, la venta se rechaza antes de generar inconsistencia.

La disponibilidad se sacrifica solo para la operacion afectada. El resto del sistema puede seguir consultando catalogo, clientes o historial central mientras la sucursal caida no participe en la transaccion.

### 5. Ejemplo de fallo
Si el nodo sucursal norte falla durante una venta:
- el nodo central no confirma la venta
- el stock no queda descontado parcialmente
- la transaccion termina `ABORTED`
- otras sucursales siguen disponibles para sus propias operaciones

### 6. Recuperacion
La recuperacion se basa en:
- trazabilidad en `distributed_transactions`
- reintento posterior cuando el nodo local vuelve a estar disponible
- aislamiento de la falla a una sucursal concreta en vez de comprometer todo el dominio de clientes o productos
