# Arquitectura distribuida Libre Mercado

## 1. Arquitectura general
Libre Mercado utiliza una aplicacion PHP como coordinador y cuatro conexiones PDO:
- una para el nodo central
- una para cada nodo de sucursal

La distribucion ya no se hace por modulo aislado, sino por datos globales y operacion local por sucursal.

## 2. Nodos
- Nodo central: `libre_mercado_central`
- Sucursal Norte: `libre_mercado_norte`
- Sucursal Centro: `libre_mercado_centro`
- Sucursal Sur: `libre_mercado_sur`

## 3. Comunicacion
La aplicacion consulta el nodo central para catalogo, usuarios, clientes, proveedores y ventas. Cuando una operacion depende de una sucursal, resuelve la conexion correcta segun `id_sucursal`.

## 4. Flujo de venta
1. El carrito pertenece a una sucursal.
2. La venta queda registrada en el nodo central.
3. El stock se descuenta en el nodo de la sucursal.
4. Si falla cualquiera, se hace rollback coordinado.

## 5. CAP
La eleccion sigue siendo CP:
- consistencia del inventario
- tolerancia a particiones
- sacrificio de disponibilidad solo en la sucursal afectada
