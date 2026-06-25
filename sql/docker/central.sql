CREATE DATABASE IF NOT EXISTS libre_mercado_central CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
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
