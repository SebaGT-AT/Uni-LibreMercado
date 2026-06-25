CREATE DATABASE IF NOT EXISTS libre_mercado_sur CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
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

    INSERT INTO stock_movimientos (transaction_code, id_producto, id_sucursal, cantidad, tipo)
    SELECT p_transaction_code, id_producto, id_sucursal, cantidad, 'RECUPERACION'
    FROM stock_movimientos
    WHERE transaction_code = p_transaction_code
      AND tipo = 'COMPRA'
      AND revertido = 1;
END $$

DELIMITER ;
