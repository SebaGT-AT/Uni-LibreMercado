CREATE DATABASE IF NOT EXISTS libre_mercado_central CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS libre_mercado_norte CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS libre_mercado_centro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS libre_mercado_sur CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE libre_mercado_central;

CREATE TABLE IF NOT EXISTS productos (
    id_producto INT AUTO_INCREMENT PRIMARY KEY,
    producto VARCHAR(120) NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    descripcion TEXT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_producto_activo (activo, producto)
);

CREATE TABLE IF NOT EXISTS sucursales (
    id_sucursal INT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL,
    INDEX idx_sucursal_nombre (nombre)
);

CREATE TABLE IF NOT EXISTS usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(80) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol ENUM('ADMIN', 'CLIENTE') NOT NULL DEFAULT 'CLIENTE',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_usuario_activo (activo, username)
);

CREATE TABLE IF NOT EXISTS clientes (
    id_cliente INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL,
    telefono VARCHAR(30) NOT NULL,
    id_usuario INT NOT NULL,
    CONSTRAINT fk_cliente_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario),
    CONSTRAINT uq_cliente_usuario UNIQUE (id_usuario),
    INDEX idx_cliente_nombre (nombre)
);

CREATE TABLE IF NOT EXISTS proveedores (
    id_proveedor INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL,
    telefono VARCHAR(30) NOT NULL,
    INDEX idx_proveedor_nombre (nombre)
);

CREATE TABLE IF NOT EXISTS ventas (
    id_venta INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT NOT NULL,
    id_sucursal INT NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total DECIMAL(10,2) NOT NULL DEFAULT 0,
    INDEX idx_venta_cliente (id_cliente),
    INDEX idx_venta_sucursal (id_sucursal),
    INDEX idx_venta_fecha (fecha)
);

CREATE TABLE IF NOT EXISTS detalle_venta (
    id_detalle INT AUTO_INCREMENT PRIMARY KEY,
    id_venta INT NOT NULL,
    id_producto INT NOT NULL,
    cantidad INT NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_detalle_venta FOREIGN KEY (id_venta) REFERENCES ventas(id_venta) ON DELETE CASCADE,
    INDEX idx_detalle_venta (id_venta),
    INDEX idx_detalle_venta_producto (id_producto)
);

CREATE TABLE IF NOT EXISTS distributed_transactions (
    id_transaction INT AUTO_INCREMENT PRIMARY KEY,
    transaction_code VARCHAR(60) NOT NULL UNIQUE,
    operation_type VARCHAR(40) NOT NULL,
    status ENUM('PREPARED', 'COMMITTED', 'ABORTED') NOT NULL DEFAULT 'PREPARED',
    payload JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS node_status (
    id_sucursal INT PRIMARY KEY,
    online TINYINT(1) NOT NULL DEFAULT 1,
    status_message VARCHAR(255) NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_node_status_sucursal FOREIGN KEY (id_sucursal) REFERENCES sucursales(id_sucursal)
);

INSERT INTO productos (id_producto, producto, precio, descripcion, activo) VALUES
(1, 'Notebook Lenovo', 799990.00, 'Notebook de 15 pulgadas', 1),
(2, 'Mouse Inalambrico', 19990.00, 'Mouse ergonomico USB', 1),
(3, 'Teclado Mecanico', 45990.00, 'Teclado RGB para oficina y gaming', 1)
ON DUPLICATE KEY UPDATE producto = VALUES(producto), precio = VALUES(precio), descripcion = VALUES(descripcion), activo = VALUES(activo);

INSERT INTO sucursales (id_sucursal, nombre) VALUES
(1, 'Sucursal Norte'),
(2, 'Sucursal Centro'),
(3, 'Sucursal Sur')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

INSERT INTO node_status (id_sucursal, online, status_message) VALUES
(1, 1, 'Nodo listo para operar.'),
(2, 1, 'Nodo listo para operar.'),
(3, 1, 'Nodo listo para operar.')
ON DUPLICATE KEY UPDATE online = VALUES(online), status_message = VALUES(status_message);

INSERT INTO usuarios (id_usuario, username, password, rol, activo) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/.AT5h4C8Qk1lW', 'ADMIN', 1),
(2, 'cliente1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/.AT5h4C8Qk1lW', 'CLIENTE', 1),
(3, 'cliente2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/.AT5h4C8Qk1lW', 'CLIENTE', 1)
ON DUPLICATE KEY UPDATE username = VALUES(username), password = VALUES(password), rol = VALUES(rol), activo = VALUES(activo);

INSERT INTO clientes (id_cliente, nombre, telefono, id_usuario) VALUES
(1, 'Ana Perez', '+56911111111', 2),
(2, 'Juan Soto', '+56922222222', 3)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), telefono = VALUES(telefono), id_usuario = VALUES(id_usuario);

INSERT INTO proveedores (id_proveedor, nombre, telefono) VALUES
(1, 'Tecno Proveedor', '+56933333333'),
(2, 'Distribuidora Central', '+56944444444')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), telefono = VALUES(telefono);

DROP PROCEDURE IF EXISTS sp_registrar_venta;
DROP PROCEDURE IF EXISTS sp_registrar_detalle_venta;
DROP PROCEDURE IF EXISTS sp_actualizar_total_venta;
DROP PROCEDURE IF EXISTS sp_set_node_status;

DELIMITER $$

CREATE PROCEDURE sp_registrar_venta(
    IN p_id_cliente INT,
    IN p_id_sucursal INT,
    IN p_fecha DATETIME
)
BEGIN
    INSERT INTO ventas (id_cliente, id_sucursal, fecha, total)
    VALUES (p_id_cliente, p_id_sucursal, p_fecha, 0);

    SELECT LAST_INSERT_ID() AS id_venta;
END $$

CREATE PROCEDURE sp_registrar_detalle_venta(
    IN p_id_venta INT,
    IN p_id_producto INT,
    IN p_cantidad INT,
    IN p_precio DECIMAL(10,2)
)
BEGIN
    INSERT INTO detalle_venta (id_venta, id_producto, cantidad, precio)
    VALUES (p_id_venta, p_id_producto, p_cantidad, p_precio);
END $$

CREATE PROCEDURE sp_actualizar_total_venta(
    IN p_id_venta INT
)
BEGIN
    UPDATE ventas
    SET total = (
        SELECT COALESCE(SUM(cantidad * precio), 0)
        FROM detalle_venta
        WHERE id_venta = p_id_venta
    )
    WHERE id_venta = p_id_venta;
END $$

CREATE PROCEDURE sp_set_node_status(
    IN p_id_sucursal INT,
    IN p_online TINYINT,
    IN p_status_message VARCHAR(255)
)
BEGIN
    INSERT INTO node_status (id_sucursal, online, status_message)
    VALUES (p_id_sucursal, p_online, p_status_message)
    ON DUPLICATE KEY UPDATE
        online = VALUES(online),
        status_message = VALUES(status_message);
END $$

DELIMITER ;

USE libre_mercado_norte;

CREATE TABLE IF NOT EXISTS stock (
    id_stock INT AUTO_INCREMENT PRIMARY KEY,
    id_producto INT NOT NULL,
    id_sucursal INT NOT NULL,
    cantidad INT NOT NULL DEFAULT 0,
    CONSTRAINT uq_stock UNIQUE (id_producto, id_sucursal),
    INDEX idx_stock_producto (id_producto)
);

CREATE TABLE IF NOT EXISTS compras (
    id_compra INT AUTO_INCREMENT PRIMARY KEY,
    id_proveedor INT NOT NULL,
    id_sucursal INT NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_compra_fecha (fecha)
);

CREATE TABLE IF NOT EXISTS detalle_compras (
    id_detalle INT AUTO_INCREMENT PRIMARY KEY,
    id_compra INT NOT NULL,
    id_producto INT NOT NULL,
    cantidad INT NOT NULL,
    costo DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_detalle_compra FOREIGN KEY (id_compra) REFERENCES compras(id_compra) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS carrito (
    id_carrito INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT NOT NULL,
    id_sucursal INT NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS detalle_carrito (
    id_detalle INT AUTO_INCREMENT PRIMARY KEY,
    id_carrito INT NOT NULL,
    id_producto INT NOT NULL,
    cantidad INT NOT NULL,
    CONSTRAINT fk_detalle_carrito FOREIGN KEY (id_carrito) REFERENCES carrito(id_carrito) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS stock_movimientos (
    id_movimiento INT AUTO_INCREMENT PRIMARY KEY,
    transaction_code VARCHAR(60) NOT NULL,
    id_producto INT NOT NULL,
    id_sucursal INT NOT NULL,
    cantidad INT NOT NULL,
    tipo ENUM('COMPRA','RECUPERACION','AJUSTE') NOT NULL,
    revertido TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_stock_mov_txn (transaction_code),
    INDEX idx_stock_mov_producto (id_producto, id_sucursal)
);

INSERT INTO stock (id_producto, id_sucursal, cantidad) VALUES
(1, 1, 8),
(2, 1, 15),
(3, 1, 10)
ON DUPLICATE KEY UPDATE cantidad = VALUES(cantidad);

INSERT INTO compras (id_compra, id_proveedor, id_sucursal, fecha) VALUES
(1, 1, 1, NOW())
ON DUPLICATE KEY UPDATE id_proveedor = VALUES(id_proveedor), id_sucursal = VALUES(id_sucursal), fecha = VALUES(fecha);

INSERT INTO detalle_compras (id_compra, id_producto, cantidad, costo) VALUES
(1, 1, 4, 620000.00),
(1, 2, 10, 9000.00);

INSERT INTO carrito (id_carrito, id_cliente, id_sucursal, fecha) VALUES
(1, 1, 1, NOW())
ON DUPLICATE KEY UPDATE id_cliente = VALUES(id_cliente), id_sucursal = VALUES(id_sucursal), fecha = VALUES(fecha);

INSERT INTO detalle_carrito (id_carrito, id_producto, cantidad) VALUES
(1, 2, 2);

DROP PROCEDURE IF EXISTS sp_actualizar_stock;
DROP PROCEDURE IF EXISTS sp_realizar_compra;
DROP PROCEDURE IF EXISTS sp_reconstruir_stock;

DELIMITER $$

CREATE PROCEDURE sp_actualizar_stock(
    IN p_id_producto INT,
    IN p_id_sucursal INT,
    IN p_delta INT,
    IN p_transaction_code VARCHAR(60)
)
BEGIN
    DECLARE v_stock_actual INT DEFAULT NULL;

    SELECT cantidad
    INTO v_stock_actual
    FROM stock
    WHERE id_producto = p_id_producto AND id_sucursal = p_id_sucursal
    FOR UPDATE;

    IF v_stock_actual IS NULL THEN
        SET v_stock_actual = 0;
        INSERT INTO stock (id_producto, id_sucursal, cantidad)
        VALUES (p_id_producto, p_id_sucursal, 0)
        ON DUPLICATE KEY UPDATE cantidad = cantidad;
    END IF;

    IF (v_stock_actual + p_delta) < 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Stock insuficiente para actualizar inventario.';
    END IF;

    UPDATE stock
    SET cantidad = cantidad + p_delta
    WHERE id_producto = p_id_producto AND id_sucursal = p_id_sucursal;

    INSERT INTO stock_movimientos (transaction_code, id_producto, id_sucursal, cantidad, tipo)
    VALUES (p_transaction_code, p_id_producto, p_id_sucursal, ABS(p_delta), 'AJUSTE');
END $$

CREATE PROCEDURE sp_realizar_compra(
    IN p_transaction_code VARCHAR(60),
    IN p_id_producto INT,
    IN p_id_sucursal INT,
    IN p_cantidad INT
)
BEGIN
    DECLARE v_stock_actual INT DEFAULT NULL;

    SELECT cantidad
    INTO v_stock_actual
    FROM stock
    WHERE id_producto = p_id_producto AND id_sucursal = p_id_sucursal
    FOR UPDATE;

    IF v_stock_actual IS NULL OR v_stock_actual < p_cantidad THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Stock insuficiente en la sucursal seleccionada.';
    END IF;

    UPDATE stock
    SET cantidad = cantidad - p_cantidad
    WHERE id_producto = p_id_producto AND id_sucursal = p_id_sucursal;

    INSERT INTO stock_movimientos (transaction_code, id_producto, id_sucursal, cantidad, tipo)
    VALUES (p_transaction_code, p_id_producto, p_id_sucursal, p_cantidad, 'COMPRA');
END $$

CREATE PROCEDURE sp_reconstruir_stock(
    IN p_transaction_code VARCHAR(60)
)
BEGIN
    UPDATE stock s
    INNER JOIN stock_movimientos m
        ON m.id_producto = s.id_producto
       AND m.id_sucursal = s.id_sucursal
    SET s.cantidad = s.cantidad + m.cantidad,
        m.revertido = 1
    WHERE m.transaction_code = p_transaction_code
      AND m.tipo = 'COMPRA'
      AND m.revertido = 0;
END $$

DELIMITER ;

USE libre_mercado_centro;

CREATE TABLE IF NOT EXISTS stock (
    id_stock INT AUTO_INCREMENT PRIMARY KEY,
    id_producto INT NOT NULL,
    id_sucursal INT NOT NULL,
    cantidad INT NOT NULL DEFAULT 0,
    CONSTRAINT uq_stock UNIQUE (id_producto, id_sucursal),
    INDEX idx_stock_producto (id_producto)
);

CREATE TABLE IF NOT EXISTS compras (
    id_compra INT AUTO_INCREMENT PRIMARY KEY,
    id_proveedor INT NOT NULL,
    id_sucursal INT NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_compra_fecha (fecha)
);

CREATE TABLE IF NOT EXISTS detalle_compras (
    id_detalle INT AUTO_INCREMENT PRIMARY KEY,
    id_compra INT NOT NULL,
    id_producto INT NOT NULL,
    cantidad INT NOT NULL,
    costo DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_detalle_compra FOREIGN KEY (id_compra) REFERENCES compras(id_compra) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS carrito (
    id_carrito INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT NOT NULL,
    id_sucursal INT NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS detalle_carrito (
    id_detalle INT AUTO_INCREMENT PRIMARY KEY,
    id_carrito INT NOT NULL,
    id_producto INT NOT NULL,
    cantidad INT NOT NULL,
    CONSTRAINT fk_detalle_carrito FOREIGN KEY (id_carrito) REFERENCES carrito(id_carrito) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS stock_movimientos (
    id_movimiento INT AUTO_INCREMENT PRIMARY KEY,
    transaction_code VARCHAR(60) NOT NULL,
    id_producto INT NOT NULL,
    id_sucursal INT NOT NULL,
    cantidad INT NOT NULL,
    tipo ENUM('COMPRA','RECUPERACION','AJUSTE') NOT NULL,
    revertido TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_stock_mov_txn (transaction_code),
    INDEX idx_stock_mov_producto (id_producto, id_sucursal)
);

INSERT INTO stock (id_producto, id_sucursal, cantidad) VALUES
(1, 2, 12),
(2, 2, 20),
(3, 2, 7)
ON DUPLICATE KEY UPDATE cantidad = VALUES(cantidad);

INSERT INTO compras (id_compra, id_proveedor, id_sucursal, fecha) VALUES
(1, 2, 2, NOW())
ON DUPLICATE KEY UPDATE id_proveedor = VALUES(id_proveedor), id_sucursal = VALUES(id_sucursal), fecha = VALUES(fecha);

INSERT INTO detalle_compras (id_compra, id_producto, cantidad, costo) VALUES
(1, 3, 6, 25000.00);

INSERT INTO carrito (id_carrito, id_cliente, id_sucursal, fecha) VALUES
(1, 2, 2, NOW())
ON DUPLICATE KEY UPDATE id_cliente = VALUES(id_cliente), id_sucursal = VALUES(id_sucursal), fecha = VALUES(fecha);

INSERT INTO detalle_carrito (id_carrito, id_producto, cantidad) VALUES
(1, 3, 1);

DROP PROCEDURE IF EXISTS sp_actualizar_stock;
DROP PROCEDURE IF EXISTS sp_realizar_compra;
DROP PROCEDURE IF EXISTS sp_reconstruir_stock;

DELIMITER $$

CREATE PROCEDURE sp_actualizar_stock(
    IN p_id_producto INT,
    IN p_id_sucursal INT,
    IN p_delta INT,
    IN p_transaction_code VARCHAR(60)
)
BEGIN
    DECLARE v_stock_actual INT DEFAULT NULL;

    SELECT cantidad
    INTO v_stock_actual
    FROM stock
    WHERE id_producto = p_id_producto AND id_sucursal = p_id_sucursal
    FOR UPDATE;

    IF v_stock_actual IS NULL THEN
        SET v_stock_actual = 0;
        INSERT INTO stock (id_producto, id_sucursal, cantidad)
        VALUES (p_id_producto, p_id_sucursal, 0)
        ON DUPLICATE KEY UPDATE cantidad = cantidad;
    END IF;

    IF (v_stock_actual + p_delta) < 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Stock insuficiente para actualizar inventario.';
    END IF;

    UPDATE stock
    SET cantidad = cantidad + p_delta
    WHERE id_producto = p_id_producto AND id_sucursal = p_id_sucursal;

    INSERT INTO stock_movimientos (transaction_code, id_producto, id_sucursal, cantidad, tipo)
    VALUES (p_transaction_code, p_id_producto, p_id_sucursal, ABS(p_delta), 'AJUSTE');
END $$

CREATE PROCEDURE sp_realizar_compra(
    IN p_transaction_code VARCHAR(60),
    IN p_id_producto INT,
    IN p_id_sucursal INT,
    IN p_cantidad INT
)
BEGIN
    DECLARE v_stock_actual INT DEFAULT NULL;

    SELECT cantidad
    INTO v_stock_actual
    FROM stock
    WHERE id_producto = p_id_producto AND id_sucursal = p_id_sucursal
    FOR UPDATE;

    IF v_stock_actual IS NULL OR v_stock_actual < p_cantidad THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Stock insuficiente en la sucursal seleccionada.';
    END IF;

    UPDATE stock
    SET cantidad = cantidad - p_cantidad
    WHERE id_producto = p_id_producto AND id_sucursal = p_id_sucursal;

    INSERT INTO stock_movimientos (transaction_code, id_producto, id_sucursal, cantidad, tipo)
    VALUES (p_transaction_code, p_id_producto, p_id_sucursal, p_cantidad, 'COMPRA');
END $$

CREATE PROCEDURE sp_reconstruir_stock(
    IN p_transaction_code VARCHAR(60)
)
BEGIN
    UPDATE stock s
    INNER JOIN stock_movimientos m
        ON m.id_producto = s.id_producto
       AND m.id_sucursal = s.id_sucursal
    SET s.cantidad = s.cantidad + m.cantidad,
        m.revertido = 1
    WHERE m.transaction_code = p_transaction_code
      AND m.tipo = 'COMPRA'
      AND m.revertido = 0;
END $$

DELIMITER ;

USE libre_mercado_sur;

CREATE TABLE IF NOT EXISTS stock (
    id_stock INT AUTO_INCREMENT PRIMARY KEY,
    id_producto INT NOT NULL,
    id_sucursal INT NOT NULL,
    cantidad INT NOT NULL DEFAULT 0,
    CONSTRAINT uq_stock UNIQUE (id_producto, id_sucursal),
    INDEX idx_stock_producto (id_producto)
);

CREATE TABLE IF NOT EXISTS compras (
    id_compra INT AUTO_INCREMENT PRIMARY KEY,
    id_proveedor INT NOT NULL,
    id_sucursal INT NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_compra_fecha (fecha)
);

CREATE TABLE IF NOT EXISTS detalle_compras (
    id_detalle INT AUTO_INCREMENT PRIMARY KEY,
    id_compra INT NOT NULL,
    id_producto INT NOT NULL,
    cantidad INT NOT NULL,
    costo DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_detalle_compra FOREIGN KEY (id_compra) REFERENCES compras(id_compra) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS carrito (
    id_carrito INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT NOT NULL,
    id_sucursal INT NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS detalle_carrito (
    id_detalle INT AUTO_INCREMENT PRIMARY KEY,
    id_carrito INT NOT NULL,
    id_producto INT NOT NULL,
    cantidad INT NOT NULL,
    CONSTRAINT fk_detalle_carrito FOREIGN KEY (id_carrito) REFERENCES carrito(id_carrito) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS stock_movimientos (
    id_movimiento INT AUTO_INCREMENT PRIMARY KEY,
    transaction_code VARCHAR(60) NOT NULL,
    id_producto INT NOT NULL,
    id_sucursal INT NOT NULL,
    cantidad INT NOT NULL,
    tipo ENUM('COMPRA','RECUPERACION','AJUSTE') NOT NULL,
    revertido TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_stock_mov_txn (transaction_code),
    INDEX idx_stock_mov_producto (id_producto, id_sucursal)
);

INSERT INTO stock (id_producto, id_sucursal, cantidad) VALUES
(1, 3, 5),
(2, 3, 20),
(3, 3, 14)
ON DUPLICATE KEY UPDATE cantidad = VALUES(cantidad);

DROP PROCEDURE IF EXISTS sp_actualizar_stock;
DROP PROCEDURE IF EXISTS sp_realizar_compra;
DROP PROCEDURE IF EXISTS sp_reconstruir_stock;

DELIMITER $$

CREATE PROCEDURE sp_actualizar_stock(
    IN p_id_producto INT,
    IN p_id_sucursal INT,
    IN p_delta INT,
    IN p_transaction_code VARCHAR(60)
)
BEGIN
    DECLARE v_stock_actual INT DEFAULT NULL;

    SELECT cantidad
    INTO v_stock_actual
    FROM stock
    WHERE id_producto = p_id_producto AND id_sucursal = p_id_sucursal
    FOR UPDATE;

    IF v_stock_actual IS NULL THEN
        SET v_stock_actual = 0;
        INSERT INTO stock (id_producto, id_sucursal, cantidad)
        VALUES (p_id_producto, p_id_sucursal, 0)
        ON DUPLICATE KEY UPDATE cantidad = cantidad;
    END IF;

    IF (v_stock_actual + p_delta) < 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Stock insuficiente para actualizar inventario.';
    END IF;

    UPDATE stock
    SET cantidad = cantidad + p_delta
    WHERE id_producto = p_id_producto AND id_sucursal = p_id_sucursal;

    INSERT INTO stock_movimientos (transaction_code, id_producto, id_sucursal, cantidad, tipo)
    VALUES (p_transaction_code, p_id_producto, p_id_sucursal, ABS(p_delta), 'AJUSTE');
END $$

CREATE PROCEDURE sp_realizar_compra(
    IN p_transaction_code VARCHAR(60),
    IN p_id_producto INT,
    IN p_id_sucursal INT,
    IN p_cantidad INT
)
BEGIN
    DECLARE v_stock_actual INT DEFAULT NULL;

    SELECT cantidad
    INTO v_stock_actual
    FROM stock
    WHERE id_producto = p_id_producto AND id_sucursal = p_id_sucursal
    FOR UPDATE;

    IF v_stock_actual IS NULL OR v_stock_actual < p_cantidad THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Stock insuficiente en la sucursal seleccionada.';
    END IF;

    UPDATE stock
    SET cantidad = cantidad - p_cantidad
    WHERE id_producto = p_id_producto AND id_sucursal = p_id_sucursal;

    INSERT INTO stock_movimientos (transaction_code, id_producto, id_sucursal, cantidad, tipo)
    VALUES (p_transaction_code, p_id_producto, p_id_sucursal, p_cantidad, 'COMPRA');
END $$

CREATE PROCEDURE sp_reconstruir_stock(
    IN p_transaction_code VARCHAR(60)
)
BEGIN
    UPDATE stock s
    INNER JOIN stock_movimientos m
        ON m.id_producto = s.id_producto
       AND m.id_sucursal = s.id_sucursal
    SET s.cantidad = s.cantidad + m.cantidad,
        m.revertido = 1
    WHERE m.transaction_code = p_transaction_code
      AND m.tipo = 'COMPRA'
      AND m.revertido = 0;
END $$

DELIMITER ;
