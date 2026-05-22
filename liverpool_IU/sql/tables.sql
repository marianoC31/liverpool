CREATE TABLE cliente_liverpool (
    id_cliente INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50),
    apellido VARCHAR(50),
    correo VARCHAR(100),
    telefono VARCHAR(15),
    fecha_nacimiento DATE
);

CREATE TABLE departamento (
    id_departamento INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50)
);

CREATE TABLE caja_cobro (
    id_caja INT PRIMARY KEY,
    estado ENUM('abierta','cerrada')
);

CREATE TABLE categoria_gourmet (
    id_categoria_g INT PRIMARY KEY,
    nombre VARCHAR(30)
);

CREATE TABLE proveedor ( 
    id_proveedor INT PRIMARY KEY, 
    nombre VARCHAR(50), 
    rfc VARCHAR(13), 
    razon_social VARCHAR(100), 
    direccion VARCHAR(100), 
    telefono VARCHAR(10)
);

CREATE TABLE promocion (
    id_promo INT PRIMARY KEY,
    porcentaje INT,
    fecha_inicio DATE,
    fecha_fin DATE,
    nombre VARCHAR(30),
    tipo VARCHAR(30),
    estado ENUM('activa','inactiva')
);


CREATE TABLE metodo_pago (
	id_metodo INT AUTO_INCREMENT PRIMARY KEY,
	tipo	VARCHAR(20)
); 

CREATE TABLE item (
    id_item INT PRIMARY KEY AUTO_INCREMENT,
    tipo_item ENUM('PRODUCTO', 'VUELO', 'ALOJAMIENTO', 'ACTIVIDAD', 'BOLETO_DISNEY', 'PRODUCTO_GOURMET', 'PLATILLO', 'PAQUETE') NOT NULL,
    nombre_comun VARCHAR(100)
); 

--  nivel 1
CREATE TABLE personal (
  id_personal INT PRIMARY KEY,
  nombre VARCHAR(30),
  apellido_materno VARCHAR(30),
  apellido_paterno VARCHAR(30),
  puesto VARCHAR(50),
  fecha_ingreso DATE,
  fecha_nacimiento DATE,
  salario DECIMAL(12,2),
  id_manager INT,
  id_departamento INT,
  FOREIGN KEY (id_departamento) REFERENCES departamento(id_departamento)
);

ALTER TABLE personal
ADD CONSTRAINT fk_manager
FOREIGN KEY (id_manager)
REFERENCES personal(id_personal);

CREATE TABLE categoria (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    id_departamento INT,
    nombre VARCHAR(50),
    FOREIGN KEY(id_departamento) REFERENCES departamento (id_departamento)
);

--  nivel 2

CREATE TABLE producto (
    id_producto INT PRIMARY KEY,
    nombre VARCHAR(50),
    precio DECIMAL(12,2),
    id_categoria INT,
    marca VARCHAR(30),
    temporada VARCHAR(30),
    FOREIGN KEY (id_producto) REFERENCES item(id_item) ON DELETE CASCADE,
    FOREIGN KEY (id_categoria) REFERENCES categoria(id_categoria)
);

CREATE TABLE vuelo (
    id_vuelo INT PRIMARY KEY,
    origen VARCHAR(30),
    destino VARCHAR(30),
    aereolinea VARCHAR(30),
    precio DECIMAL(12,2),
    fecha_salida DATETIME,
    fecha_llegada DATETIME,
    FOREIGN KEY (id_vuelo) REFERENCES item(id_item) ON DELETE CASCADE
);

CREATE TABLE alojamiento (
    id_alojamiento INT PRIMARY KEY,
    personas INT,
    ubicacion VARCHAR(30),
    precio_por_noche DECIMAL(12,2),
    FOREIGN KEY (id_alojamiento) REFERENCES item(id_item) ON DELETE CASCADE
);

CREATE TABLE actividad (
    id_activity INT PRIMARY KEY,
    nombre VARCHAR(30),
    precio_por_adulto DECIMAL(12,2),
    duracion INT,
    calificacion INT,
    FOREIGN KEY (id_activity) REFERENCES item(id_item) ON DELETE CASCADE
);

CREATE TABLE boletos_disney (
    id_boletos INT PRIMARY KEY,
    tipo VARCHAR(30),
    precio DECIMAL(12,2),
    FOREIGN KEY (id_boletos) REFERENCES item(id_item) ON DELETE CASCADE
);

--  nivel 3

CREATE TABLE corte_caja (
    id_corte INT PRIMARY KEY,
    id_caja INT,
    monto_inicial DECIMAL(12,2),
    monto_final DECIMAL(12,2),
    diferencia DECIMAL (12,2),
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    id_supervisor INT,
    FOREIGN KEY (id_supervisor) REFERENCES personal (id_personal),
    FOREIGN KEY (id_caja) REFERENCES caja_cobro(id_caja)
);

CREATE TABLE paquete (
    id_paquete INT PRIMARY KEY,
    id_alojamiento INT,
    personas INT,
    fecha_inicio DATETIME,
    fecha_fin DATETIME,
    precio_paquete DECIMAL(12,2),
    FOREIGN KEY (id_paquete) REFERENCES item(id_item) ON DELETE CASCADE,
    FOREIGN KEY (id_alojamiento) REFERENCES alojamiento(id_alojamiento) ON DELETE SET NULL
);

CREATE TABLE producto_gourmet (
    id_producto_g INT PRIMARY KEY,
    nombre VARCHAR(30),
    marca VARCHAR(30),
    precio DECIMAL(10,2),
    es_organico BOOLEAN,
    peso DECIMAL(10,2),
    temperatura_alm VARCHAR(30),
    id_categoria_g INT,
    FOREIGN KEY (id_producto_g) REFERENCES item (id_item),
    FOREIGN KEY (id_categoria_g) REFERENCES categoria_gourmet(id_categoria_g)
);

CREATE TABLE platillo (
    id_platillo INT PRIMARY KEY,
    nombre VARCHAR(50),
    precio DECIMAL(10,2),
    id_categoria_g INT,
    FOREIGN KEY (id_platillo) REFERENCES item (id_item),
    FOREIGN KEY (id_categoria_g) REFERENCES categoria_gourmet(id_categoria_g)
);

--  nivel 4

CREATE TABLE venta (
    id_venta INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT, 
    id_caja INT,
    id_cajero INT,
    total DECIMAL(12,2),
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_cajero) REFERENCES personal (id_personal),
    FOREIGN KEY (id_cliente) REFERENCES cliente_liverpool(id_cliente),
    FOREIGN KEY (id_caja) REFERENCES caja_cobro (id_caja)
);

CREATE TABLE venta_detalle (
    id_detalle INT AUTO_INCREMENT PRIMARY KEY,
    id_venta INT,
    id_item INT,
    cantidad INT,
    precio_unitario DECIMAL(10,2),
    descuento DECIMAL(10,2),
    subtotal DECIMAL(10,2),
    FOREIGN KEY (id_venta) REFERENCES venta(id_venta),
    FOREIGN KEY (id_item) REFERENCES item(id_item)
);
--  nivel 5

CREATE TABLE venta_detalle_alojamiento (
    id_detalle INT PRIMARY KEY,
    id_alojamiento INT NOT NULL,
    fecha_entrada DATETIME,
    fecha_salida DATETIME,
    personas INT,
    FOREIGN KEY (id_detalle) REFERENCES venta_detalle(id_detalle) ON DELETE CASCADE,
    FOREIGN KEY (id_alojamiento) REFERENCES alojamiento(id_alojamiento)
);

CREATE TABLE devolucion (
    id_devolucion INT PRIMARY KEY AUTO_INCREMENT,
    id_detalle INT NOT NULL, 
    monto DECIMAL(12,2) NOT NULL,
    fecha DATE NOT NULL,
    motivo VARCHAR(50),
    FOREIGN KEY (id_detalle) REFERENCES venta_detalle(id_detalle) ON DELETE CASCADE
);

CREATE TABLE venta_promo (
    id_ventapromo INT PRIMARY KEY AUTO_INCREMENT,
    id_promo INT NOT NULL,   
    id_detalle INT NOT NULL,
    FOREIGN KEY (id_detalle) REFERENCES venta_detalle(id_detalle) ON DELETE CASCADE,
    FOREIGN KEY (id_promo) REFERENCES promocion(id_promo)
);

CREATE TABLE factura_venta (
    id_factura INT PRIMARY KEY AUTO_INCREMENT,
    id_venta INT UNIQUE NOT NULL, 
    id_cliente INT NOT NULL,
    folio VARCHAR(20) NOT NULL,
    fecha DATE NOT NULL,
    rfc_receptor VARCHAR(15) NOT NULL,
    nombre_receptor VARCHAR(50),
    subtotal DECIMAL(12,2) NOT NULL,
    iva DECIMAL(12,2) NOT NULL,
    total DECIMAL(12,2) NOT NULL,
    estado ENUM('VIGENTE', 'CANCELADA') DEFAULT 'VIGENTE',
    regimen_fiscal VARCHAR(100),
    uso_cfdi VARCHAR(20),
    FOREIGN KEY (id_venta) REFERENCES venta(id_venta),
    FOREIGN KEY (id_cliente) REFERENCES cliente_liverpool (id_cliente)
);

CREATE TABLE compra (
    id_compra INT AUTO_INCREMENT PRIMARY KEY,
    id_proveedor INT,
    fecha DATE,
    costo_compra DECIMAL(12,2),
    estado ENUM('PENDIENTE', 'RECIBIDO'),
    FOREIGN KEY (id_proveedor) REFERENCES proveedor(id_proveedor)
);

CREATE TABLE compra_detalle (
    id_compra_detalle INT AUTO_INCREMENT PRIMARY KEY,
    id_producto INT,
    id_compra INT,
    cantidad INT,
    costo_unitario DECIMAL(12,2),
    FOREIGN KEY (id_producto) REFERENCES producto(id_producto),
    FOREIGN KEY (id_compra) REFERENCES compra(id_compra)
);

CREATE TABLE factura_compra (
    id_factura INT PRIMARY KEY AUTO_INCREMENT,
    id_compra INT UNIQUE NOT NULL,
    id_proveedor INT NOT NULL,
    folio VARCHAR(20) NOT NULL,
    fecha DATE NOT NULL,
    rfc_emisor VARCHAR(15) NOT NULL,
    nombre_emisor VARCHAR(50),
    subtotal DECIMAL(12,2) NOT NULL,
    iva DECIMAL(12,2) NOT NULL,
    total DECIMAL(12,2) NOT NULL,
    estado ENUM('VIGENTE', 'CANCELADA') DEFAULT 'VIGENTE',
    regimen_fiscal VARCHAR(100),
    uso_cfdi VARCHAR(20),
    FOREIGN KEY (id_compra) REFERENCES compra(id_compra),
    FOREIGN KEY (id_proveedor) REFERENCES proveedor(id_proveedor)
);

CREATE TABLE inventario (
    id_inventario INT AUTO_INCREMENT PRIMARY KEY,
    id_producto INT,
    stock_actual INT,
    stock_minimo INT,
    ultima_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_producto) REFERENCES producto(id_producto)
);

CREATE TABLE lote (
    id_lote INT PRIMARY KEY,
    id_producto_g INT,
    id_proveedor INT,
    fecha_ingreso DATE,
    fecha_caducidad DATE,
    cantidad_actual INT,
    cantidad_recibida INT,
    FOREIGN KEY (id_producto_g) REFERENCES producto_gourmet(id_producto_g),
    FOREIGN KEY (id_proveedor) REFERENCES proveedor(id_proveedor)
);

CREATE TABLE producto_promo (
    id_producto INT,
    id_promo INT,
    PRIMARY KEY (id_producto, id_promo),
    FOREIGN KEY (id_producto) REFERENCES producto(id_producto),
    FOREIGN KEY (id_promo) REFERENCES promocion(id_promo)
);

CREATE TABLE paquete_vuelo (
    id_paquete INT,
    id_vuelo INT,
    cantidad INT,
    PRIMARY KEY (id_paquete, id_vuelo),
    FOREIGN KEY (id_paquete) REFERENCES paquete(id_paquete),
    FOREIGN KEY (id_vuelo) REFERENCES vuelo(id_vuelo)
);

--  tarjetas de credito

CREATE TABLE tarjeta (
    id_tarjeta INT PRIMARY KEY AUTO_INCREMENT,
    marca VARCHAR(20)
);


CREATE TABLE tarjeta_tienda (
    id_tarjeta INT PRIMARY KEY,
    id_cliente INT NOT NULL,
    num_tarjeta VARCHAR(16) NOT NULL UNIQUE,
    fecha_registro DATE NOT NULL,
    fecha_vencimiento DATE NOT NULL,
    estado ENUM('activa','inactiva') NOT NULL DEFAULT 'activa',
    FOREIGN KEY (id_tarjeta) REFERENCES tarjeta(id_tarjeta),
   FOREIGN KEY (id_cliente) REFERENCES cliente_liverpool(id_cliente)
); 

CREATE TABLE contrato_credito (
    id_contrato INT PRIMARY KEY AUTO_INCREMENT,
    id_cliente INT NOT NULL,
    tipo_tarjeta ENUM(
        'DEPARTAMENTAL',
        'LIVERTU',
        'VISA',
        'GARANTIZADA'
    ) NOT NULL,
    limite_credito DECIMAL(12,2) NOT NULL,
    saldo_pendiente DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    tasa_interes DECIMAL(5,4) NOT NULL,
    fecha_registro DATE NOT NULL,
    fecha_vencimiento DATE NOT NULL,
    FOREIGN KEY (id_cliente) REFERENCES cliente_liverpool(id_cliente),
    UNIQUE (id_cliente, tipo_tarjeta)
);


CREATE TABLE cuenta_activa (
   id_cuenta INT PRIMARY KEY AUTO_INCREMENT,
   id_cliente INT NOT NULL,
   clabe VARCHAR(18) NOT NULL UNIQUE,
   num_cuenta VARCHAR(11) NOT NULL UNIQUE,
   estado ENUM('activa','inactiva') NOT NULL DEFAULT 'activa',
   saldo_disponible DECIMAL(12,2) NOT NULL DEFAULT 0.00,
   rendimiento_diario DECIMAL(12,2) NOT NULL DEFAULT 0.00,
   fecha_apertura DATETIME NOT NULL,
   FOREIGN KEY (id_cliente) REFERENCES cliente_liverpool(id_cliente)
); 


CREATE TABLE monedero_cliente (
    id_monedero INT PRIMARY KEY AUTO_INCREMENT,
    id_cliente INT NOT NULL UNIQUE,
    puntos_acumulados INT NOT NULL DEFAULT 0,
    saldo_acumulado DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    fecha_actualizacion DATETIME NOT NULL,
    estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
    FOREIGN KEY (id_cliente) REFERENCES cliente_liverpool(id_cliente)
); 


CREATE TABLE tarjeta_credito (
    id_tarjeta INT PRIMARY KEY,
    id_contrato INT NOT NULL,
    FOREIGN KEY (id_tarjeta) REFERENCES tarjeta_tienda(id_tarjeta),
   FOREIGN KEY (id_contrato) REFERENCES contrato_credito(id_contrato)
);  

CREATE TABLE tarjeta_debito (
    id_tarjeta INT PRIMARY KEY,
    id_cuenta INT NOT NULL UNIQUE,
    FOREIGN KEY (id_tarjeta) REFERENCES tarjeta_tienda(id_tarjeta),
    FOREIGN KEY (id_cuenta) REFERENCES cuenta_activa(id_cuenta)
); 

CREATE TABLE movimiento_cuenta (
    id_movimiento INT PRIMARY KEY AUTO_INCREMENT,
    id_cuenta INT NOT NULL,
    monto DECIMAL(12,2) NOT NULL,
    tipo_movimiento ENUM(
                       'DEPOSITO',
                       'RETIRO',
                       'INTERES',
                       'INVERSION'
                   ) NOT NULL,
    fecha_hora TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_cuenta) REFERENCES cuenta_activa(id_cuenta)
); 

CREATE TABLE inversion (
    id_inversion INT PRIMARY KEY AUTO_INCREMENT,
    id_cuenta INT NOT NULL,
    monto DECIMAL(12,2) NOT NULL,
    fecha_ingreso DATE NOT NULL,
    estrategia ENUM(
     		         'RENDIMIENTO_DIARIO',
                    'CONSERVADORA',
                    'BALANCEADA',
                    'AGRESIVA',
                    'PROTECCION_CAMBIARIA'
                 ) NOT NULL,
    FOREIGN KEY (id_cuenta) REFERENCES cuenta_activa(id_cuenta)
); 


CREATE TABLE movimiento_puntos (
    id_movimiento INT PRIMARY KEY AUTO_INCREMENT,
    id_monedero INT NOT NULL,
    id_tarjeta INT NOT NULL,
    id_venta INT NOT NULL,
    tipo_movimiento ENUM('ACUMULACION','CANJE') NOT NULL,
    puntos INT NOT NULL,
    fecha_hora TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    establecimiento VARCHAR(50),
FOREIGN KEY (id_monedero) REFERENCES monedero_cliente(id_monedero),
    FOREIGN KEY (id_tarjeta) REFERENCES tarjeta_tienda(id_tarjeta),
    FOREIGN KEY (id_venta) REFERENCES venta(id_venta)
);


CREATE TABLE pago (
    id_pago INT PRIMARY KEY AUTO_INCREMENT,
    id_venta INT NOT NULL,
    id_metodo INT NOT NULL,
    monto DECIMAL(12,2) NOT NULL,
    fecha_pago DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_venta) REFERENCES venta(id_venta),
    FOREIGN KEY (id_metodo) REFERENCES metodo_pago(id_metodo)
);

CREATE TABLE pago_tarjeta (
    id_pago INT PRIMARY KEY,
    id_tarjeta INT NOT NULL,
    FOREIGN KEY (id_pago) REFERENCES pago(id_pago),
    FOREIGN KEY (id_tarjeta) REFERENCES tarjeta(id_tarjeta)
);

CREATE TABLE pago_deuda_credito (
    id_pago_credito INT PRIMARY KEY AUTO_INCREMENT,
    id_contrato INT NOT NULL,
    id_metodo INT NOT NULL,
    monto_pagado DECIMAL(12,2) NOT NULL,
    fecha_pago DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (id_contrato) REFERENCES contrato_credito(id_contrato),
    FOREIGN KEY (id_metodo) REFERENCES metodo_pago(id_metodo)
);
