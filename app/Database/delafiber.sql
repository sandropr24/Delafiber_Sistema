DROP DATABASE IF EXISTS delafiber_db;
CREATE DATABASE delafiber_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE delafiber_db;


-- categorias
CREATE TABLE categorias (
  idcategoria INT AUTO_INCREMENT PRIMARY KEY,
  categoria   VARCHAR(80) NOT NULL UNIQUE
);

-- marcas
CREATE TABLE marcas (
  idmarca INT AUTO_INCREMENT PRIMARY KEY,
  marca   VARCHAR(80) NOT NULL UNIQUE
);

-- tipospago
CREATE TABLE tipospago (
  idtipopago INT AUTO_INCREMENT PRIMARY KEY,
  tipopago   VARCHAR(40) NOT NULL UNIQUE
);

-- estadocotizacion
CREATE TABLE estadocotizacion (
  idestado INT AUTO_INCREMENT PRIMARY KEY,
  estado   VARCHAR(40) NOT NULL UNIQUE
);

-- estadopedido
CREATE TABLE estadopedido (
  idestado INT AUTO_INCREMENT PRIMARY KEY,
  estado   VARCHAR(40) NOT NULL UNIQUE
);

-- locales
CREATE TABLE locales (
  idlocal     INT AUTO_INCREMENT PRIMARY KEY,
  direccion   VARCHAR(150) NOT NULL,
  nombrelocal VARCHAR(80)  NOT NULL,
  tipolocal   VARCHAR(40)  NOT NULL
);

-- personas
CREATE TABLE personas (
  idpersona INT AUTO_INCREMENT PRIMARY KEY,
  apellidos VARCHAR(100) NOT NULL,
  nombres   VARCHAR(100) NOT NULL,
  tipodoc   VARCHAR(20)  NOT NULL,
  numerodoc VARCHAR(20)  NOT NULL,
  direccion VARCHAR(150) NULL,
  telefono  VARCHAR(20)  NULL,
  email     VARCHAR(100) NULL,
  UNIQUE (tipodoc, numerodoc)
);

-- proveedores
CREATE TABLE proveedores (
  idproveedor     INT AUTO_INCREMENT PRIMARY KEY,
  razonsocial     VARCHAR(150) NOT NULL,
  ruc             CHAR(11)     NOT NULL UNIQUE,
  direccion       VARCHAR(150) NULL,
  telefono        VARCHAR(20)  NULL,
  email           VARCHAR(100) NULL,
  nombrecomercial VARCHAR(150) NULL
);


-- usuarios  (necesita: personas)
CREATE TABLE usuarios (
  idusuario     INT AUTO_INCREMENT PRIMARY KEY,
  idpersona     INT NOT NULL,
  nombreusuario VARCHAR(50)  NOT NULL UNIQUE,
  claveacceso   VARCHAR(255) NOT NULL,  -- guardar hash, nunca texto plano
  rol           VARCHAR(30)  NOT NULL,
  estado        TINYINT(1)   NOT NULL DEFAULT 1,
  FOREIGN KEY (idpersona) REFERENCES personas(idpersona)
);

-- compras  (necesita: proveedores)
CREATE TABLE compras (
  idcompra        INT AUTO_INCREMENT PRIMARY KEY,
  idproveedor     INT NOT NULL,
  serie           VARCHAR(20) NOT NULL,
  fechacompra     DATE NOT NULL,
  fecharegistro   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  tipocomprobante VARCHAR(30) NOT NULL,
  total           DECIMAL(10,2) NOT NULL CHECK (total >= 0),
  FOREIGN KEY (idproveedor) REFERENCES proveedores(idproveedor)
);

-- productos  (necesita: categorias, marcas)
CREATE TABLE productos (
  idproducto   INT AUTO_INCREMENT PRIMARY KEY,
  idcategoria  INT NOT NULL,
  descripcion  VARCHAR(150) NOT NULL,
  idmarca      INT NOT NULL,
  modelo       VARCHAR(80) NULL,
  codigobarras VARCHAR(50) NULL UNIQUE,
  estado       TINYINT(1) NOT NULL DEFAULT 1,
  imagen       VARCHAR(255)
  precioventa  DECIMAL(10,2) NOT NULL CHECK (precioventa >= 0),
  FOREIGN KEY (idcategoria) REFERENCES categorias(idcategoria),
  FOREIGN KEY (idmarca)     REFERENCES marcas(idmarca)
);


-- detcompra  (necesita: compras, productos)
CREATE TABLE detcompra (
  iddetcompra  INT AUTO_INCREMENT PRIMARY KEY,
  cantidad     INT NOT NULL CHECK (cantidad > 0),
  preciocompra DECIMAL(10,2) NOT NULL CHECK (preciocompra >= 0),
  idcompra     INT NOT NULL,
  idproducto   INT NOT NULL,
  FOREIGN KEY (idcompra)   REFERENCES compras(idcompra),
  FOREIGN KEY (idproducto) REFERENCES productos(idproducto)
);

-- kardex  (necesita: locales, productos)
CREATE TABLE kardex (
  idkardex      INT AUTO_INCREMENT PRIMARY KEY,
  idproducto    INT NOT NULL,
  minima        INT NOT NULL DEFAULT 0,
  maxima        INT NOT NULL DEFAULT 0,
  idlocal       INT NOT NULL,
  stockactual   INT NOT NULL DEFAULT 0,
  costopromedio DECIMAL(10,2) NOT NULL DEFAULT 0,
  UNIQUE (idproducto, idlocal),
  FOREIGN KEY (idproducto) REFERENCES productos(idproducto),
  FOREIGN KEY (idlocal)    REFERENCES locales(idlocal)
);

-- cotizacion  (necesita: estadocotizacion, locales, personas, usuarios)
CREATE TABLE cotizacion (
  idcotizacion     INT AUTO_INCREMENT PRIMARY KEY,
  fecha            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fechavencimiento DATE NOT NULL,
  observacion      VARCHAR(255) NULL,
  idestado         INT NOT NULL,
  idlocal          INT NOT NULL,
  idusuario        INT NOT NULL,
  idpersona        INT NOT NULL,
  FOREIGN KEY (idestado)  REFERENCES estadocotizacion(idestado),
  FOREIGN KEY (idlocal)   REFERENCES locales(idlocal),
  FOREIGN KEY (idusuario) REFERENCES usuarios(idusuario),
  FOREIGN KEY (idpersona) REFERENCES personas(idpersona)
);


-- movimientos  (necesita: compras, kardex, usuarios)
CREATE TABLE movimientos (
  idmovimiento INT AUTO_INCREMENT PRIMARY KEY,
  idkardex     INT NOT NULL,
  tipo         VARCHAR(20) NOT NULL,
  fecha        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  cantidad     INT NOT NULL,
  saldo        INT NOT NULL,
  descripcion  VARCHAR(150) NULL,
  idusuario    INT NOT NULL,
  motivo       VARCHAR(100) NULL,
  idcompra     INT NULL,
  FOREIGN KEY (idkardex)  REFERENCES kardex(idkardex),
  FOREIGN KEY (idusuario) REFERENCES usuarios(idusuario),
  FOREIGN KEY (idcompra)  REFERENCES compras(idcompra)
);

-- detcotizacion  (necesita: cotizacion, productos)
CREATE TABLE detcotizacion (
  iddetcotizacion INT AUTO_INCREMENT PRIMARY KEY,
  cantidad        INT NOT NULL CHECK (cantidad > 0),
  preciounitario  DECIMAL(10,2) NOT NULL CHECK (preciounitario >= 0),
  idcotizacion    INT NOT NULL,
  idproducto      INT NOT NULL,
  total           DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (idcotizacion) REFERENCES cotizacion(idcotizacion),
  FOREIGN KEY (idproducto)   REFERENCES productos(idproducto)
);

-- pedido  (necesita: cotizacion, estadopedido, locales, personas, usuarios)
CREATE TABLE pedido (
  idpedido     INT AUTO_INCREMENT PRIMARY KEY,
  tipopedido   VARCHAR(30) NOT NULL,
  idestado     INT NOT NULL,
  idcotizacion INT NULL,
  fecha        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  idlocal      INT NOT NULL,
  idusuario    INT NOT NULL,
  idpersona    INT NOT NULL,
  FOREIGN KEY (idestado)     REFERENCES estadopedido(idestado),
  FOREIGN KEY (idcotizacion) REFERENCES cotizacion(idcotizacion),
  FOREIGN KEY (idlocal)      REFERENCES locales(idlocal),
  FOREIGN KEY (idusuario)    REFERENCES usuarios(idusuario),
  FOREIGN KEY (idpersona)    REFERENCES personas(idpersona)
);


-- detpedido  (necesita: pedido, productos)
CREATE TABLE detpedido (
  iddetpedido    INT AUTO_INCREMENT PRIMARY KEY,
  cantidad       INT NOT NULL CHECK (cantidad > 0),
  preciounitario DECIMAL(10,2) NOT NULL CHECK (preciounitario >= 0),
  total          DECIMAL(10,2) NOT NULL,
  idpedido       INT NOT NULL,
  idproducto     INT NOT NULL,
  FOREIGN KEY (idpedido)   REFERENCES pedido(idpedido),
  FOREIGN KEY (idproducto) REFERENCES productos(idproducto)
);

-- ventas  (necesita: locales, pedido, personas, usuarios)
CREATE TABLE ventas (
  idventa      INT AUTO_INCREMENT PRIMARY KEY,
  idcliente    INT NOT NULL,              -- apunta a personas
  idlocal      INT NOT NULL,
  tipodocumento VARCHAR(20) NOT NULL,
  serie        VARCHAR(10) NOT NULL,
  numero       VARCHAR(20) NOT NULL,
  idcajero     INT NOT NULL,              -- apunta a usuarios
  estafacturado TINYINT(1) NOT NULL DEFAULT 0,
  cdr          VARCHAR(255) NULL,
  idpedido     INT NULL,
  fecha        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  total        DECIMAL(10,2) NOT NULL CHECK (total >= 0),
  UNIQUE (tipodocumento, serie, numero),
  FOREIGN KEY (idcliente) REFERENCES personas(idpersona),
  FOREIGN KEY (idlocal)   REFERENCES locales(idlocal),
  FOREIGN KEY (idcajero)  REFERENCES usuarios(idusuario),
  FOREIGN KEY (idpedido)  REFERENCES pedido(idpedido)
);


-- detventa  (necesita: productos, ventas)
CREATE TABLE detventa (
  iddetventa    INT AUTO_INCREMENT PRIMARY KEY,
  cantidad      INT NOT NULL CHECK (cantidad > 0),
  precioventa   DECIMAL(10,2) NOT NULL CHECK (precioventa >= 0),
  costounitario DECIMAL(10,2) NOT NULL CHECK (costounitario >= 0),
  idventa       INT NOT NULL,
  idproducto    INT NOT NULL,
  FOREIGN KEY (idventa)    REFERENCES ventas(idventa),
  FOREIGN KEY (idproducto) REFERENCES productos(idproducto)
);

-- pagoventa  (necesita: tipospago, ventas)
CREATE TABLE pagoventa (
  idpagoventa   INT AUTO_INCREMENT PRIMARY KEY,
  idventa       INT NOT NULL,
  idtipopago    INT NOT NULL,
  monto         DECIMAL(10,2) NOT NULL CHECK (monto > 0),
  referencia    VARCHAR(50) NULL,         -- nro de operacion Yape/tarjeta
  montorecibido DECIMAL(10,2) NULL,       -- solo efectivo
  vuelto        DECIMAL(10,2) NULL,       -- solo efectivo
  fecha         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (idventa)    REFERENCES ventas(idventa),
  FOREIGN KEY (idtipopago) REFERENCES tipospago(idtipopago)
);



SELECT * FROM productos;
