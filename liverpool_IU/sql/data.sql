INSERT INTO departamento 
VALUES (1, 'Ropa'), (2, 'Electrónica'), (3, 'Hogar');

INSERT INTO categoria VALUES
(1, 1, 'Ropa Casual'),
(2, 2, 'Smartphones'),
(3, 3, 'Muebles');

INSERT INTO caja_cobro VALUES (1, 'abierta'), (2, 'abierta');

INSERT INTO personal VALUES
(1, 'Laura',   'García',   'Pérez',   'Cajera',   '2022-01-10', '1998-05-20', 12000.00, NULL, 1),
(2, 'Carlos',  'López',    'Sánchez', 'Supervisor','2020-03-15', '1985-11-30', 20000.00, NULL, 2),
(3, 'Sofía',   'Martínez', 'Torres',  'Cajera',   '2023-06-01', '2000-02-14', 12000.00, 2,    1);

UPDATE personal SET id_manager = 2 WHERE id_personal = 1;
UPDATE personal SET id_manager = 2 WHERE id_personal = 3;

INSERT INTO cliente_liverpool VALUES
(1, 'Ana', 'Ramírez', 'ana@mail.com', '5512345678', '1995-03-10'),
(2, 'Pedro', 'Flores', 'pedro@mail.com', '5587654321', '1990-07-22'),
(3, 'Lucía', 'Mendoza', 'lucia@mail.com', '5599887766', '1988-12-05');

INSERT INTO proveedor VALUES
(1, 'Textiles SA',  'TSA123456789', 'Textiles SA de CV',  'CDMX',       '5511112222'),
(2, 'TechImport',   'TIM987654321', 'TechImport SA de CV','Monterrey',  '8112223333'),
(3, 'MueblesNor',   'MNO456789123', 'MueblesNor SA de CV','Guadalajara','3313334444');

INSERT INTO metodo_pago VALUES
(1, 'EFECTIVO'),
(2, 'TARJETA_DEBITO'),
(3, 'TARJETA_CREDITO'),
(4, 'TRANSFERENCIA');

INSERT INTO promocion VALUES
(1, 20, '2026-01-01', '2026-04-15', 'Rebajas Enero',  'DESCUENTO', 'inactiva'),
(2, 10, '2026-04-01', '2026-06-30', 'Promo Primavera','DESCUENTO', 'activa');

--Items y productos
INSERT INTO item VALUES
(1, 'PRODUCTO', 'Playera Casual'),
(2, 'PRODUCTO', 'iPhone 15'),
(3, 'PRODUCTO', 'Silla de Oficina'),
(4, 'PRODUCTO', 'Jeans Slim'),
(5, 'PRODUCTO', 'Audífonos Bluetooth');

INSERT INTO producto VALUES
(1, 'Playera Casual',      299.00,  1, 'Nike',    'Primavera'),
(2, 'iPhone 15',           18999.00, 2, 'Apple',   'Todo el año'),
(3, 'Silla de Oficina',    2499.00,  3, 'Segno',   'Todo el año'),
(4, 'Jeans Slim',          699.00,   1, 'Levis', 'Otoño'),
(5, 'Audífonos Bluetooth', 1299.00,  2, 'Sony',    'Todo el año');

INSERT INTO producto_promo VALUES
(1, 2), 
(3, 2); 

--Proveedores 
INSERT INTO compra VALUES
(1, 1, '2026-03-01', 15000.00, 'RECIBIDO'),
(2, 2, '2026-03-05', 95000.00, 'RECIBIDO'),
(3, 3, '2026-03-10', 50000.00, 'RECIBIDO');

INSERT INTO compra_detalle VALUES
(1, 1, 1, 100, 120.00), 
(2, 4, 1,  50, 280.00),  
(3, 2, 2,   5, 14000.00),
(4, 5, 2,  20, 800.00), 
(5, 3, 3,  20, 1800.00); 

INSERT INTO factura_compra VALUES
(1, 1, 1, 'FC-001', '2026-03-01', 'TSA123456789', 'Textiles SA',  12931.03, 2068.97, 15000.00, 'VIGENTE', 'GENERAL', 'G03'),
(2, 2, 2, 'FC-002', '2026-03-05', 'TIM987654321', 'TechImport',   81896.55, 13103.45, 95000.00,'VIGENTE', 'GENERAL', 'G03'),
(3, 3, 3, 'FC-003', '2026-03-10', 'MNO456789123', 'MueblesNor',   43103.45, 6896.55, 50000.00, 'VIGENTE', 'GENERAL', 'G03');

INSERT INTO inventario VALUES
(1, 1, 100, 10, CURRENT_TIMESTAMP),
(2, 2,   5,  1, CURRENT_TIMESTAMP),
(3, 3,  20,  2, CURRENT_TIMESTAMP),
(4, 4,  50,  5, CURRENT_TIMESTAMP),
(5, 5,  20,  2, CURRENT_TIMESTAMP);

--Tarjetas
INSERT INTO tarjeta VALUES
(1, 'LIVERPOOL'),
(2, 'LIVERPOOL'),
(3, 'BBVA'),
(4, 'LIVERPOOL'),
(5, 'LIVERPOOL');

INSERT INTO tarjeta_tienda VALUES
(1, 1, '4111111111111111', '2024-01-15', '2027-01-15', 'activa'),
(2, 2, '4222222222222222', '2023-06-01', '2026-06-01', 'activa'),
(4, 3, '4444444444444444', '2025-03-01', '2028-03-01', 'activa'),
(5, 3, '5555555555555555', '2025-03-01', '2028-03-01', 'activa');

INSERT INTO contrato_credito VALUES
(1, 1, 'VISA',           50000.00, 0.00, 0.2400, '2024-01-15', '2027-01-15'),
(2, 2, 'DEPARTAMENTAL',  20000.00, 0.00, 0.3600, '2023-06-01', '2026-06-01'),
(3, 3, 'VISA',           30000.00, 0.00, 0.2400, '2025-03-01', '2028-03-01');

INSERT INTO tarjeta_credito VALUES
(1, 1),
(2, 2),
(4, 3);

INSERT INTO cuenta_activa VALUES
(1, 3, '646180157000000001', '00000000001', 'activa', 15000.00, 0.00, '2025-03-01 10:00:00');

INSERT INTO tarjeta_debito VALUES (5, 1);

INSERT INTO monedero_cliente VALUES
(1, 1, 0, 0.00, NOW(), 'activo'),
(2, 2, 0, 0.00, NOW(), 'activo'),
(3, 3, 0, 0.00, NOW(), 'activo');


--Ventas
INSERT INTO venta VALUES (1, 1, 1, 1, 897.30, '2026-04-10 10:30:00');

INSERT INTO venta_detalle VALUES
(1, 1, 1, 2, 299.00, 0.00, 598.00),
(2, 1, 4, 1, 699.00, 69.90, 629.10);

INSERT INTO venta_promo VALUES (1, 2, 2);

INSERT INTO venta VALUES (2, 2, 1, 1, 15199.20, '2026-04-10 11:00:00');

INSERT INTO venta_detalle VALUES
(3, 2, 2, 1, 18999.00, 3799.80, 15199.20);

INSERT INTO venta_promo VALUES (2, 1, 3);

INSERT INTO venta VALUES (3, 3, 2, 3, 2249.10, '2026-04-12 14:00:00');

INSERT INTO venta_detalle VALUES
(4, 3, 3, 1, 2499.00, 249.90, 2249.10);

INSERT INTO venta VALUES (4, 1, 1, 1, 1897.00, '2026-04-20 16:00:00');

INSERT INTO venta_detalle VALUES
(5, 4, 5, 1, 1299.00, 0.00, 1299.00),
(6, 4, 1, 2, 299.00, 0.00, 598.00);

INSERT INTO venta VALUES (5, 2, 2, 3, 18999.00, '2026-04-25 12:00:00');

INSERT INTO venta_detalle VALUES
(7, 5, 2, 1, 18999.00, 0.00, 18999.00);


--Pagos
INSERT INTO pago VALUES
(1, 1, 1, 897.30, '2026-04-10 10:30:00'),
(2, 2, 3, 15199.20, '2026-04-10 11:00:00'),
(3, 3, 2, 2249.10, '2026-04-12 14:00:00'),
(4, 4, 3, 1897.00, '2026-04-20 16:00:00'),
(5, 5, 3, 18999.00, '2026-04-25 12:00:00');

INSERT INTO pago_tarjeta VALUES
(2, 1),
(3, 5),
(4, 1),
(5, 2);

--Movimientos de puntos
INSERT INTO movimiento_puntos VALUES
(1, 1, 1, 1, 'ACUMULACION', 89, '2026-04-10 10:30:00', 'Liverpool Perisur'),
(2, 2, 2, 2, 'ACUMULACION', 151, '2026-04-10 11:00:00', 'Liverpool Perisur'),
(3, 1, 1, 4, 'ACUMULACION', 189, '2026-04-20 16:00:00', 'Liverpool Perisur'),
(4, 2, 2, 5, 'ACUMULACION', 189, '2026-04-25 12:00:00', 'Liverpool Santa Fe');

UPDATE monedero_cliente
SET puntos_acumulados = 278,
    saldo_acumulado = 27.80,
    fecha_actualizacion = NOW()
WHERE id_cliente = 1;

UPDATE monedero_cliente
SET puntos_acumulados = 340,
    saldo_acumulado = 34.00,
    fecha_actualizacion = NOW()
WHERE id_cliente = 2;

UPDATE inventario
SET stock_actual = 96,
    ultima_actualizacion = NOW()
WHERE id_producto = 1;

UPDATE inventario
SET stock_actual = 3,
    ultima_actualizacion = NOW()
WHERE id_producto = 2;

UPDATE inventario
SET stock_actual = 19,
    ultima_actualizacion = NOW()
WHERE id_producto = 3;

UPDATE inventario
SET stock_actual = 49,
    ultima_actualizacion = NOW()
WHERE id_producto = 4;

UPDATE inventario
SET stock_actual = 19,
    ultima_actualizacion = NOW()
WHERE id_producto = 5;
