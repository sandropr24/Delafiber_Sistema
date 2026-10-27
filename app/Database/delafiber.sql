CREATE DATABASE IF NOT EXISTS delafiber_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE delafiber_db;


CREATE TABLE categorias (
  idcategoria      INT AUTO_INCREMENT PRIMARY KEY,
  idcategoriapadre INT NULL,
  categoria        VARCHAR(80) NOT NULL,
  estado           TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE (idcategoriapadre, categoria),
  FOREIGN KEY (idcategoriapadre) REFERENCES categorias(idcategoria)
);

CREATE TABLE marcas (
  idmarca INT AUTO_INCREMENT PRIMARY KEY,
  marca   VARCHAR(80) NOT NULL UNIQUE
);

-- Unidad de medida (el cable UTP se vende por metro)
CREATE TABLE unidadesmedida (
  idunidad     INT AUTO_INCREMENT PRIMARY KEY,
  unidad       VARCHAR(40) NOT NULL UNIQUE,
  abreviatura  VARCHAR(10) NOT NULL UNIQUE
);

-- Línea de negocio: define el prefijo de numeración (COT-2026-CCTV-003571)
CREATE TABLE lineas (
  idlinea INT AUTO_INCREMENT PRIMARY KEY,
  prefijo VARCHAR(15) NOT NULL UNIQUE,
  nombre  VARCHAR(60) NOT NULL,
  estado  TINYINT(1) NOT NULL DEFAULT 1
);

-- Canal de venta: tienda (Grocio), campo, servicio técnico
CREATE TABLE canalesventa (
  idcanal INT AUTO_INCREMENT PRIMARY KEY,
  canal   VARCHAR(40) NOT NULL UNIQUE
);

CREATE TABLE tipospago (
  idtipopago     INT AUTO_INCREMENT PRIMARY KEY,
  tipopago       VARCHAR(40) NOT NULL UNIQUE,
  requierecuenta TINYINT(1) NOT NULL DEFAULT 0,   -- exige cuenta destino, nº operación y voucher
  estado         TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE cuentasbancarias (
  idcuenta     INT AUTO_INCREMENT PRIMARY KEY,
  banco        VARCHAR(40) NOT NULL,
  numerocuenta VARCHAR(30) NOT NULL,
  cci          VARCHAR(30) NULL,
  moneda       ENUM('PEN','USD') NOT NULL DEFAULT 'PEN',
  estado       TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE (banco, numerocuenta)
);

CREATE TABLE estadocotizacion (
  idestado INT AUTO_INCREMENT PRIMARY KEY,
  estado   VARCHAR(40) NOT NULL UNIQUE
);

CREATE TABLE estadopedido (
  idestado INT AUTO_INCREMENT PRIMARY KEY,
  estado   VARCHAR(40) NOT NULL UNIQUE
);

CREATE TABLE estadoventa (
  idestado INT AUTO_INCREMENT PRIMARY KEY,
  estado   VARCHAR(40) NOT NULL UNIQUE
);

CREATE TABLE roles (
  idrol INT AUTO_INCREMENT PRIMARY KEY,
  rol   VARCHAR(30) NOT NULL UNIQUE
);

CREATE TABLE locales (
  idlocal     INT AUTO_INCREMENT PRIMARY KEY,
  direccion   VARCHAR(150) NOT NULL,
  nombrelocal VARCHAR(80)  NOT NULL,
  tipolocal   ENUM('TIENDA','ALMACEN','OFICINA') NOT NULL DEFAULT 'TIENDA'
);

-- Datos de empresa y parámetros: logo, colores de marca, IGV, etc.
CREATE TABLE configuracion (
  clave VARCHAR(50)  PRIMARY KEY,
  valor VARCHAR(255) NOT NULL
);

-- Tipo de cambio diario (se compra en USD, se vende en PEN)
CREATE TABLE tipocambio (
  idtipocambio INT AUTO_INCREMENT PRIMARY KEY,
  fecha        DATE NOT NULL UNIQUE,
  compra       DECIMAL(8,4) NOT NULL CHECK (compra > 0),
  venta        DECIMAL(8,4) NOT NULL CHECK (venta > 0)
);

-- Correlativos por tipo de documento, prefijo y año. Usar sp_siguiente_correlativo
CREATE TABLE correlativos (
  idcorrelativo INT AUTO_INCREMENT PRIMARY KEY,
  tipodocumento VARCHAR(20) NOT NULL,           -- COTIZACION, NOTA_VENTA
  prefijo       VARCHAR(15) NOT NULL,           -- CCTV, REDES... o serie N001
  anio          SMALLINT NOT NULL DEFAULT 0,    -- 0 = sin año
  ultimonumero  INT NOT NULL DEFAULT 0,
  UNIQUE (tipodocumento, prefijo, anio)
);

-- Soporta personas naturales y empresas (RUC)
CREATE TABLE personas (
  idpersona     INT AUTO_INCREMENT PRIMARY KEY,
  tipopersona   ENUM('NATURAL','JURIDICA') NOT NULL DEFAULT 'NATURAL',
  apellidos     VARCHAR(100) NULL,
  nombres       VARCHAR(100) NULL,
  razonsocial   VARCHAR(150) NULL,
  tipodoc       ENUM('DNI','RUC','CE','PASAPORTE') NOT NULL,
  numerodoc     VARCHAR(20)  NOT NULL,
  direccion     VARCHAR(150) NULL,
  telefono      VARCHAR(20)  NULL,
  email         VARCHAR(100) NULL,
  codigocliente VARCHAR(30)  NULL,                -- código en el sistema de internet
  UNIQUE (tipodoc, numerodoc),
  CHECK (
    (tipopersona = 'NATURAL'  AND nombres IS NOT NULL AND apellidos IS NOT NULL) OR
    (tipopersona = 'JURIDICA' AND razonsocial IS NOT NULL)
  )
);

CREATE TABLE proveedores (
  idproveedor     INT AUTO_INCREMENT PRIMARY KEY,
  razonsocial     VARCHAR(150) NOT NULL,
  ruc             CHAR(11)     NOT NULL UNIQUE,
  direccion       VARCHAR(150) NULL,
  telefono        VARCHAR(20)  NULL,
  email           VARCHAR(100) NULL,
  nombrecomercial VARCHAR(150) NULL
);

CREATE TABLE usuarios (
  idusuario     INT AUTO_INCREMENT PRIMARY KEY,
  idpersona     INT NOT NULL,
  idrol         INT NOT NULL,
  nombreusuario VARCHAR(50)  NOT NULL UNIQUE,
  claveacceso   VARCHAR(255) NOT NULL,            -- guardar hash (bcrypt/argon2), nunca texto plano
  estado        TINYINT(1)   NOT NULL DEFAULT 1,
  FOREIGN KEY (idpersona) REFERENCES personas(idpersona),
  FOREIGN KEY (idrol)     REFERENCES roles(idrol)
);

CREATE TABLE productos (
  idproducto    INT AUTO_INCREMENT PRIMARY KEY,
  idcategoria   INT NOT NULL,
  tipo          ENUM('PRODUCTO','SERVICIO','COMBO') NOT NULL DEFAULT 'PRODUCTO',
  nombre        VARCHAR(150) NOT NULL,
  descripcion   VARCHAR(150) NULL,
  idmarca       INT NULL,                          -- los servicios no tienen marca
  modelo        VARCHAR(80) NULL,
  codigobarras  VARCHAR(50) NULL UNIQUE,
  idunidad      INT NOT NULL,
  controlastock TINYINT(1) NOT NULL DEFAULT 1,
  controlaserie TINYINT(1) NOT NULL DEFAULT 0,
  garantiameses SMALLINT NOT NULL DEFAULT 0,       -- plazo de garantía por defecto
  preciocosto   DECIMAL(10,2) NOT NULL DEFAULT 0 CHECK (preciocosto >= 0),
  monedacosto   ENUM('PEN','USD') NOT NULL DEFAULT 'USD',
  precioventa   DECIMAL(10,2) NOT NULL CHECK (precioventa >= 0),
  estado        TINYINT(1) NOT NULL DEFAULT 1,
  imagen        VARCHAR(255) NULL,
  FOREIGN KEY (idcategoria) REFERENCES categorias(idcategoria),
  FOREIGN KEY (idmarca)     REFERENCES marcas(idmarca),
  FOREIGN KEY (idunidad)    REFERENCES unidadesmedida(idunidad),
  CHECK (tipo = 'PRODUCTO' OR controlaserie = 0),
  CHECK (tipo <> 'SERVICIO' OR controlastock = 0),
  CHECK (tipo <> 'COMBO'    OR controlastock = 0),   -- el combo descuenta stock de sus componentes
  CHECK (tipo = 'SERVICIO'  OR idmarca IS NOT NULL)
);

-- Historial de precios de venta
CREATE TABLE preciosventa_hist (
  idprecio    INT AUTO_INCREMENT PRIMARY KEY,
  idproducto  INT NOT NULL,
  precioanterior DECIMAL(10,2) NOT NULL,
  precionuevo    DECIMAL(10,2) NOT NULL,
  fecha       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (idproducto) REFERENCES productos(idproducto)
);

-- Componentes de un combo (ej. kit de cámaras + instalación)
CREATE TABLE combo_detalle (
  idcombo    INT NOT NULL,
  idproducto INT NOT NULL,
  cantidad   DECIMAL(10,2) NOT NULL CHECK (cantidad > 0),
  PRIMARY KEY (idcombo, idproducto),
  FOREIGN KEY (idcombo)    REFERENCES productos(idproducto),
  FOREIGN KEY (idproducto) REFERENCES productos(idproducto),
  CHECK (idcombo <> idproducto)
);

CREATE TABLE compras (
  idcompra        INT AUTO_INCREMENT PRIMARY KEY,
  idproveedor     INT NOT NULL,
  tipocomprobante VARCHAR(30) NOT NULL,
  serie           VARCHAR(20) NOT NULL,
  numero          VARCHAR(20) NOT NULL,
  fechacompra     DATE NOT NULL,
  fecharegistro   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  moneda          ENUM('PEN','USD') NOT NULL DEFAULT 'USD',
  tipocambio      DECIMAL(8,4) NOT NULL DEFAULT 1 CHECK (tipocambio > 0),
  total           DECIMAL(10,2) NOT NULL CHECK (total >= 0),
  UNIQUE (idproveedor, tipocomprobante, serie, numero),
  FOREIGN KEY (idproveedor) REFERENCES proveedores(idproveedor)
);

CREATE TABLE detcompra (
  iddetcompra  INT AUTO_INCREMENT PRIMARY KEY,
  idcompra     INT NOT NULL,
  idproducto   INT NOT NULL,
  cantidad     DECIMAL(10,2) NOT NULL CHECK (cantidad > 0),
  preciocompra DECIMAL(10,2) NOT NULL CHECK (preciocompra >= 0),   -- en la moneda de la compra
  FOREIGN KEY (idcompra)   REFERENCES compras(idcompra),
  FOREIGN KEY (idproducto) REFERENCES productos(idproducto)
);

-- Stock por producto y local (los movimientos están en la tabla movimientos)
CREATE TABLE kardex (
  idkardex      INT AUTO_INCREMENT PRIMARY KEY,
  idproducto    INT NOT NULL,
  idlocal       INT NOT NULL,
  minima        DECIMAL(10,2) NOT NULL DEFAULT 0,
  maxima        DECIMAL(10,2) NOT NULL DEFAULT 0,
  stockactual   DECIMAL(10,2) NOT NULL DEFAULT 0 CHECK (stockactual >= 0),
  costopromedio DECIMAL(12,4) NOT NULL DEFAULT 0,  -- en PEN
  UNIQUE (idproducto, idlocal),
  FOREIGN KEY (idproducto) REFERENCES productos(idproducto),
  FOREIGN KEY (idlocal)    REFERENCES locales(idlocal)
);

CREATE TABLE cotizacion (
  idcotizacion     INT AUTO_INCREMENT PRIMARY KEY,
  numero           VARCHAR(30) NOT NULL UNIQUE,    -- COT-2026-CCTV-003571
  idlinea          INT NOT NULL,
  fecha            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fechavencimiento DATE NOT NULL,
  tipocambio       DECIMAL(8,4) NOT NULL DEFAULT 1 CHECK (tipocambio > 0),
  porcentajeigv    DECIMAL(5,2) NOT NULL DEFAULT 18.00 CHECK (porcentajeigv >= 0),
  subtotal         DECIMAL(10,2) NOT NULL DEFAULT 0,
  montoigv         DECIMAL(10,2) NOT NULL DEFAULT 0,
  total            DECIMAL(10,2) NOT NULL DEFAULT 0,
  observacion      VARCHAR(255) NULL,
  idestado         INT NOT NULL,
  idlocal          INT NOT NULL,
  idusuario        INT NOT NULL,
  idpersona        INT NOT NULL,
  CHECK (fechavencimiento >= DATE(fecha)),
  CHECK (total = subtotal + montoigv),
  FOREIGN KEY (idlinea)   REFERENCES lineas(idlinea),
  FOREIGN KEY (idestado)  REFERENCES estadocotizacion(idestado),
  FOREIGN KEY (idlocal)   REFERENCES locales(idlocal),
  FOREIGN KEY (idusuario) REFERENCES usuarios(idusuario),
  FOREIGN KEY (idpersona) REFERENCES personas(idpersona)
);

CREATE TABLE detcotizacion (
  iddetcotizacion INT AUTO_INCREMENT PRIMARY KEY,
  idcotizacion    INT NOT NULL,
  idproducto      INT NOT NULL,
  cantidad        DECIMAL(10,2) NOT NULL CHECK (cantidad > 0),
  preciounitario  DECIMAL(10,2) NOT NULL CHECK (preciounitario >= 0),
  total           DECIMAL(10,2) GENERATED ALWAYS AS (ROUND(cantidad * preciounitario, 2)) STORED,
  FOREIGN KEY (idcotizacion) REFERENCES cotizacion(idcotizacion),
  FOREIGN KEY (idproducto)   REFERENCES productos(idproducto)
);

-- Opcional: no aparece en los requerimientos. Eliminar si no se usa.
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

CREATE TABLE detpedido (
  iddetpedido    INT AUTO_INCREMENT PRIMARY KEY,
  idpedido       INT NOT NULL,
  idproducto     INT NOT NULL,
  cantidad       DECIMAL(10,2) NOT NULL CHECK (cantidad > 0),
  preciounitario DECIMAL(10,2) NOT NULL CHECK (preciounitario >= 0),
  total          DECIMAL(10,2) GENERATED ALWAYS AS (ROUND(cantidad * preciounitario, 2)) STORED,
  FOREIGN KEY (idpedido)   REFERENCES pedido(idpedido),
  FOREIGN KEY (idproducto) REFERENCES productos(idproducto)
);


CREATE TABLE ventas (
  idventa          INT AUTO_INCREMENT PRIMARY KEY,
  idcliente        INT NOT NULL,
  idlocal          INT NOT NULL,
  idlinea          INT NOT NULL,
  idcanal          INT NOT NULL,
  tipodocumento    ENUM('NOTA_VENTA','BOLETA','FACTURA') NOT NULL DEFAULT 'NOTA_VENTA',
  serie            VARCHAR(10) NOT NULL,
  numero           VARCHAR(20) NOT NULL,
  idcajero         INT NOT NULL,                   -- quien registra
  idvendedor       INT NOT NULL,                   -- quien vende (reportes por vendedor)
  idestado         INT NOT NULL,                   -- Emitida / Anulada
  idcotizacion     INT NULL UNIQUE,                -- una proforma se convierte una sola vez
  idpedido         INT NULL,
  fecha            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  formapago        ENUM('CONTADO','CUOTAS','ADELANTO_SALDO','CREDITO_INTERNET') NOT NULL DEFAULT 'CONTADO',
  montoinicial     DECIMAL(10,2) NULL CHECK (montoinicial >= 0),
  nrocuotas        SMALLINT NULL CHECK (nrocuotas > 0),
  frecuenciacuotas ENUM('SEMANAL','QUINCENAL','MENSUAL') NULL,
  subtotal         DECIMAL(10,2) NOT NULL DEFAULT 0,
  montoigv         DECIMAL(10,2) NOT NULL DEFAULT 0,
  descuento        DECIMAL(10,2) NOT NULL DEFAULT 0 CHECK (descuento >= 0),
  total            DECIMAL(10,2) NOT NULL CHECK (total >= 0),
  fechaanulacion   DATETIME NULL,
  idusuarioanula   INT NULL,
  motivoanulacion  VARCHAR(150) NULL,
  estafacturado    TINYINT(1) NOT NULL DEFAULT 0,  -- reservado para SUNAT
  cdr              VARCHAR(255) NULL,              -- reservado para SUNAT
  UNIQUE (tipodocumento, serie, numero),
  CHECK (total = subtotal + montoigv - descuento),
  CHECK (formapago <> 'CUOTAS' OR (nrocuotas IS NOT NULL AND frecuenciacuotas IS NOT NULL)),
  FOREIGN KEY (idcliente)      REFERENCES personas(idpersona),
  FOREIGN KEY (idlocal)        REFERENCES locales(idlocal),
  FOREIGN KEY (idlinea)        REFERENCES lineas(idlinea),
  FOREIGN KEY (idcanal)        REFERENCES canalesventa(idcanal),
  FOREIGN KEY (idcajero)       REFERENCES usuarios(idusuario),
  FOREIGN KEY (idvendedor)     REFERENCES usuarios(idusuario),
  FOREIGN KEY (idestado)       REFERENCES estadoventa(idestado),
  FOREIGN KEY (idcotizacion)   REFERENCES cotizacion(idcotizacion),
  FOREIGN KEY (idpedido)       REFERENCES pedido(idpedido),
  FOREIGN KEY (idusuarioanula) REFERENCES usuarios(idusuario)
);

CREATE TABLE detventa (
  iddetventa    INT AUTO_INCREMENT PRIMARY KEY,
  idventa       INT NOT NULL,
  idproducto    INT NOT NULL,
  cantidad      DECIMAL(10,2) NOT NULL CHECK (cantidad > 0),
  precioventa   DECIMAL(10,2) NOT NULL CHECK (precioventa >= 0),
  costounitario DECIMAL(10,2) NOT NULL CHECK (costounitario >= 0),
  garantiameses SMALLINT NOT NULL DEFAULT 0,       -- plazo vigente al momento de la venta
  total         DECIMAL(10,2) GENERATED ALWAYS AS (ROUND(cantidad * precioventa, 2)) STORED,
  FOREIGN KEY (idventa)    REFERENCES ventas(idventa),
  FOREIGN KEY (idproducto) REFERENCES productos(idproducto)
);

-- Número de serie por unidad física
CREATE TABLE series (
  idserie     INT AUTO_INCREMENT PRIMARY KEY,
  idproducto  INT NOT NULL,
  numeroserie VARCHAR(60) NOT NULL,
  idlocal     INT NULL,
  estado      ENUM('EN_STOCK','VENDIDO','EN_GARANTIA','BAJA') NOT NULL DEFAULT 'EN_STOCK',
  iddetcompra INT NULL,
  iddetventa  INT NULL,
  UNIQUE (idproducto, numeroserie),
  FOREIGN KEY (idproducto)  REFERENCES productos(idproducto),
  FOREIGN KEY (idlocal)     REFERENCES locales(idlocal),
  FOREIGN KEY (iddetcompra) REFERENCES detcompra(iddetcompra),
  FOREIGN KEY (iddetventa)  REFERENCES detventa(iddetventa)
);

-- Kardex real: toda entrada/salida con su origen
CREATE TABLE movimientos (
  idmovimiento INT AUTO_INCREMENT PRIMARY KEY,
  idkardex     INT NOT NULL,
  tipo         ENUM('ENTRADA','SALIDA','AJUSTE','ANULACION') NOT NULL,
  fecha        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  cantidad     DECIMAL(10,2) NOT NULL,
  saldo        DECIMAL(10,2) NOT NULL,
  descripcion  VARCHAR(150) NULL,
  motivo       VARCHAR(100) NULL,
  idusuario    INT NOT NULL,
  idcompra     INT NULL,
  idventa      INT NULL,
  FOREIGN KEY (idkardex)  REFERENCES kardex(idkardex),
  FOREIGN KEY (idusuario) REFERENCES usuarios(idusuario),
  FOREIGN KEY (idcompra)  REFERENCES compras(idcompra),
  FOREIGN KEY (idventa)   REFERENCES ventas(idventa)
);



-- Cronograma de cuotas (pago inicial + saldo en cuotas)
CREATE TABLE cuotas (
  idcuota          INT AUTO_INCREMENT PRIMARY KEY,
  idventa          INT NOT NULL,
  nrocuota         SMALLINT NOT NULL,
  fechavencimiento DATE NOT NULL,
  monto            DECIMAL(10,2) NOT NULL CHECK (monto > 0),
  montopagado      DECIMAL(10,2) NOT NULL DEFAULT 0 CHECK (montopagado >= 0),
  estado           ENUM('PENDIENTE','PARCIAL','PAGADA') NOT NULL DEFAULT 'PENDIENTE',
  UNIQUE (idventa, nrocuota),
  CHECK (montopagado <= monto),
  FOREIGN KEY (idventa) REFERENCES ventas(idventa)
);

-- Puntos de venta / cajas
CREATE TABLE cajas (
  idcaja  INT AUTO_INCREMENT PRIMARY KEY,
  idlocal INT NOT NULL,
  nombre  VARCHAR(60) NOT NULL,
  estado  TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE (idlocal, nombre),
  FOREIGN KEY (idlocal) REFERENCES locales(idlocal)
);

-- Apertura y cierre (independiente de caja chica)
CREATE TABLE aperturacaja (
  idapertura       INT AUTO_INCREMENT PRIMARY KEY,
  idcaja           INT NOT NULL,
  idusuario        INT NOT NULL,
  fechaapertura    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  montoinicial     DECIMAL(10,2) NOT NULL CHECK (montoinicial >= 0),
  fechacierre      DATETIME NULL,
  estado           ENUM('ABIERTA','CERRADA') NOT NULL DEFAULT 'ABIERTA',
  observacion      VARCHAR(255) NULL,
  -- impide dos aperturas abiertas en la misma caja
  idcaja_abierta   INT GENERATED ALWAYS AS (IF(estado = 'ABIERTA', idcaja, NULL)) STORED,
  UNIQUE (idcaja_abierta),
  CHECK (estado = 'ABIERTA' OR fechacierre IS NOT NULL),
  FOREIGN KEY (idcaja)    REFERENCES cajas(idcaja),
  FOREIGN KEY (idusuario) REFERENCES usuarios(idusuario)
);

-- Arqueo por medio de pago
CREATE TABLE cierrecaja_detalle (
  idapertura    INT NOT NULL,
  idtipopago    INT NOT NULL,
  montoesperado DECIMAL(10,2) NOT NULL,
  montocontado  DECIMAL(10,2) NOT NULL,
  diferencia    DECIMAL(10,2) GENERATED ALWAYS AS (montocontado - montoesperado) STORED,
  observacion   VARCHAR(150) NULL,
  PRIMARY KEY (idapertura, idtipopago),
  FOREIGN KEY (idapertura) REFERENCES aperturacaja(idapertura),
  FOREIGN KEY (idtipopago) REFERENCES tipospago(idtipopago)
);

-- Pagos: uno o varios por venta (pago mixto), también abonos a cuotas
CREATE TABLE pagoventa (
  idpagoventa     INT AUTO_INCREMENT PRIMARY KEY,
  idventa         INT NOT NULL,
  idcuota         INT NULL,
  idtipopago      INT NOT NULL,
  idapertura      INT NULL,                         -- NULL hasta que el técnico rinda el cobro en caja
  idusuariocobro  INT NOT NULL,                     -- cajero o técnico que cobró
  monto           DECIMAL(10,2) NOT NULL CHECK (monto > 0),
  idcuenta        INT NULL,                         -- cuenta destino (pagos no efectivo)
  referencia      VARCHAR(50) NULL,                 -- nº de operación
  voucher         VARCHAR(255) NULL,                -- ruta de la foto del voucher
  montorecibido   DECIMAL(10,2) NULL,
  vuelto          DECIMAL(10,2) NULL,
  fecha           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (idventa)        REFERENCES ventas(idventa),
  FOREIGN KEY (idcuota)        REFERENCES cuotas(idcuota),
  FOREIGN KEY (idtipopago)     REFERENCES tipospago(idtipopago),
  FOREIGN KEY (idapertura)     REFERENCES aperturacaja(idapertura),
  FOREIGN KEY (idusuariocobro) REFERENCES usuarios(idusuario),
  FOREIGN KEY (idcuenta)       REFERENCES cuentasbancarias(idcuenta)
);

-- Crédito cargado al recibo de internet (se factura en el otro sistema)
CREATE TABLE creditosinternet (
  idcredito      INT AUTO_INCREMENT PRIMARY KEY,
  idventa        INT NOT NULL UNIQUE,
  idpersona      INT NOT NULL,
  codigocliente  VARCHAR(30) NULL,
  monto          DECIMAL(10,2) NOT NULL CHECK (monto > 0),
  nrocuotas      SMALLINT NOT NULL DEFAULT 1 CHECK (nrocuotas > 0),
  estado         ENUM('PENDIENTE','COBRADO') NOT NULL DEFAULT 'PENDIENTE',
  fecharegistro  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fechacobro     DATETIME NULL,
  idusuariocobro INT NULL,
  CHECK (estado = 'PENDIENTE' OR fechacobro IS NOT NULL),
  FOREIGN KEY (idventa)        REFERENCES ventas(idventa),
  FOREIGN KEY (idpersona)      REFERENCES personas(idpersona),
  FOREIGN KEY (idusuariocobro) REFERENCES usuarios(idusuario)
);

CREATE TABLE entregas (
  identrega          INT AUTO_INCREMENT PRIMARY KEY,
  idventa            INT NOT NULL UNIQUE,
  tipoentrega        ENUM('RECOJO_TIENDA','DOMICILIO') NOT NULL,
  direccion          VARCHAR(150) NULL,
  referencia         VARCHAR(150) NULL,
  telefono           VARCHAR(20)  NULL,
  idtecnico          INT NULL,
  estado             ENUM('PENDIENTE','EN_CAMINO','ENTREGADO') NOT NULL DEFAULT 'PENDIENTE',
  fechaencamino      DATETIME NULL,
  fechaentrega       DATETIME NULL,
  cobrocontraentrega TINYINT(1) NOT NULL DEFAULT 0,
  idpagoventa        INT NULL,                       -- pago cobrado por el técnico
  CHECK (tipoentrega = 'RECOJO_TIENDA'
         OR (direccion IS NOT NULL AND telefono IS NOT NULL AND idtecnico IS NOT NULL)),
  CHECK (estado <> 'ENTREGADO' OR fechaentrega IS NOT NULL),
  FOREIGN KEY (idventa)     REFERENCES ventas(idventa),
  FOREIGN KEY (idtecnico)   REFERENCES usuarios(idusuario),
  FOREIGN KEY (idpagoventa) REFERENCES pagoventa(idpagoventa)
);


CREATE TABLE garantias (
  idgarantia    INT AUTO_INCREMENT PRIMARY KEY,
  iddetventa    INT NOT NULL,
  idserie       INT NULL,
  fechaingreso  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  falla         VARCHAR(255) NOT NULL,
  estado        ENUM('RECIBIDO','ENVIADO_PROVEEDOR','REPARADO','REEMPLAZADO','ENTREGADO_CLIENTE')
                NOT NULL DEFAULT 'RECIBIDO',
  idproveedor   INT NULL,
  idusuario     INT NOT NULL,
  observacion   VARCHAR(255) NULL,
  FOREIGN KEY (iddetventa) REFERENCES detventa(iddetventa),
  FOREIGN KEY (idserie)    REFERENCES series(idserie),
  FOREIGN KEY (idproveedor) REFERENCES proveedores(idproveedor),
  FOREIGN KEY (idusuario)  REFERENCES usuarios(idusuario)
);

CREATE TABLE garantia_historial (
  idhistorial INT AUTO_INCREMENT PRIMARY KEY,
  idgarantia  INT NOT NULL,
  estado      ENUM('RECIBIDO','ENVIADO_PROVEEDOR','REPARADO','REEMPLAZADO','ENTREGADO_CLIENTE') NOT NULL,
  fecha       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  observacion VARCHAR(255) NULL,
  idusuario   INT NOT NULL,
  FOREIGN KEY (idgarantia) REFERENCES garantias(idgarantia),
  FOREIGN KEY (idusuario)  REFERENCES usuarios(idusuario)
);

-- =====================================================================
-- 10. ÍNDICES PARA REPORTES
-- =====================================================================
CREATE INDEX ix_ventas_fecha     ON ventas(fecha);
CREATE INDEX ix_pagoventa_fecha  ON pagoventa(fecha);
CREATE INDEX ix_cuotas_venc      ON cuotas(fechavencimiento, estado);
CREATE INDEX ix_entregas_estado  ON entregas(idtecnico, estado);
CREATE INDEX ix_movimientos_kdx  ON movimientos(idkardex, fecha);

-- =====================================================================
-- 11. DATOS INICIALES
-- =====================================================================
INSERT INTO roles (rol) VALUES ('Administrador'),('Vendedor'),('Cajero'),('Técnico');

INSERT INTO estadocotizacion (estado) VALUES
 ('Borrador'),('Enviada'),('Aceptada'),('Rechazada'),('Vencida'),('Convertida en venta');
INSERT INTO estadopedido (estado) VALUES ('Pendiente'),('Atendido'),('Anulado');
INSERT INTO estadoventa  (estado) VALUES ('Emitida'),('Anulada');

INSERT INTO tipospago (tipopago, requierecuenta) VALUES
 ('Efectivo',0),('Yape',0),('Plin',0),('QR de pagos',0),('Transferencia / Depósito',1);

INSERT INTO cuentasbancarias (banco, numerocuenta) VALUES
 ('BCP','POR_DEFINIR'),('BBVA','POR_DEFINIR'),('Interbank','POR_DEFINIR');

INSERT INTO canalesventa (canal) VALUES ('Tienda (Grocio)'),('Venta en campo'),('Servicio técnico');
INSERT INTO lineas (prefijo, nombre) VALUES
 ('CCTV','Seguridad y videovigilancia'),('REDES','Redes'),
 ('RADIO','Radioenlaces'),('COMPUTO','Cómputo y servicios');

INSERT INTO unidadesmedida (unidad, abreviatura) VALUES
 ('Unidad','UND'),('Metro','M'),('Servicio','SERV');

INSERT INTO categorias (categoria) VALUES
 ('Cómputo y accesorios'),('Energía y protección'),('Impresoras y tintas'),
 ('Seguridad y videovigilancia'),('Redes'),('Radioenlaces'),('Servicios');

INSERT INTO configuracion (clave, valor) VALUES
 ('empresa_nombre','DELAFIBER'),
 ('logo','POR_DEFINIR'),
 ('color_primario','#32005A'),
 ('color_secundario','#FF2B50'),
 ('igv_porcentaje','18.00'),
 ('precios_incluyen_igv','POR_DEFINIR');   -- pendiente de confirmar con gerencia

-- Numeración: la primera proforma de cada línea será ...-003571
INSERT INTO correlativos (tipodocumento, prefijo, anio, ultimonumero) VALUES
 ('COTIZACION','CCTV',2026,3570),('COTIZACION','REDES',2026,3570),
 ('COTIZACION','RADIO',2026,3570),('COTIZACION','COMPUTO',2026,3570),
 ('NOTA_VENTA','N001',0,0);

-- =====================================================================
-- 12. PROCEDIMIENTO DE CORRELATIVO Y TRIGGER DE PRECIOS
-- =====================================================================
DELIMITER $$

-- Devuelve el siguiente número sin duplicados, aun con usuarios concurrentes
CREATE PROCEDURE sp_siguiente_correlativo(
  IN  p_tipo   VARCHAR(20),
  IN  p_prefijo VARCHAR(15),
  IN  p_anio   SMALLINT,
  OUT p_numero INT
)
BEGIN
  UPDATE correlativos
     SET ultimonumero = LAST_INSERT_ID(ultimonumero + 1)
   WHERE tipodocumento = p_tipo AND prefijo = p_prefijo AND anio = p_anio;

  IF ROW_COUNT() = 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Correlativo no configurado';
  END IF;

  SET p_numero = LAST_INSERT_ID();
END$$

CREATE TRIGGER trg_productos_precio
AFTER UPDATE ON productos
FOR EACH ROW
BEGIN
  IF OLD.precioventa <> NEW.precioventa THEN
    INSERT INTO preciosventa_hist (idproducto, precioanterior, precionuevo)
    VALUES (NEW.idproducto, OLD.precioventa, NEW.precioventa);
  END IF;
END$$

DELIMITER ;

-- =====================================================================
-- 13. VISTAS PARA REPORTES
-- =====================================================================

-- Ventas con saldo pendiente (adelantos, cuotas, proyectos)
CREATE VIEW vw_ventas_saldo AS
SELECT v.idventa, v.tipodocumento, v.serie, v.numero, v.fecha, v.idcliente, v.total,
       COALESCE(p.pagado,0) AS pagado,
       v.total - COALESCE(p.pagado,0) AS saldo
FROM ventas v
JOIN estadoventa ev ON ev.idestado = v.idestado AND ev.estado = 'Emitida'
LEFT JOIN (SELECT idventa, SUM(monto) AS pagado FROM pagoventa GROUP BY idventa) p
       ON p.idventa = v.idventa
WHERE v.formapago <> 'CREDITO_INTERNET'
HAVING saldo > 0;

-- Cuotas vencidas (para el aviso)
CREATE VIEW vw_cuotas_vencidas AS
SELECT c.idcuota, c.idventa, v.serie, v.numero, v.idcliente, c.nrocuota,
       c.fechavencimiento, c.monto - c.montopagado AS pendiente,
       DATEDIFF(CURDATE(), c.fechavencimiento) AS diasvencida
FROM cuotas c
JOIN ventas v ON v.idventa = c.idventa
WHERE c.estado <> 'PAGADA' AND c.fechavencimiento < CURDATE();

-- Créditos por trasladar a la facturación de internet
CREATE VIEW vw_creditos_internet_pendientes AS
SELECT ci.idcredito, ci.idventa, v.serie, v.numero, ci.idpersona, ci.codigocliente,
       pe.numerodoc, ci.monto, ci.nrocuotas, ci.fecharegistro
FROM creditosinternet ci
JOIN ventas v    ON v.idventa = ci.idventa
JOIN personas pe ON pe.idpersona = ci.idpersona
WHERE ci.estado = 'PENDIENTE';

-- Entregas pendientes por técnico
CREATE VIEW vw_entregas_pendientes_tecnico AS
SELECT e.idtecnico, CONCAT(pe.apellidos,' ',pe.nombres) AS tecnico,
       e.identrega, e.idventa, v.serie, v.numero, e.direccion, e.referencia,
       e.telefono, e.estado, e.cobrocontraentrega
FROM entregas e
JOIN ventas v    ON v.idventa = e.idventa
JOIN usuarios u  ON u.idusuario = e.idtecnico
JOIN personas pe ON pe.idpersona = u.idpersona
WHERE e.tipoentrega = 'DOMICILIO' AND e.estado <> 'ENTREGADO';

-- Búsqueda de garantía por serie, DNI o número de venta
CREATE VIEW vw_series_vendidas AS
SELECT s.idserie, s.numeroserie, pr.nombre AS producto,
       v.idventa, v.serie, v.numero, v.fecha AS fechaventa,
       pe.tipodoc, pe.numerodoc, COALESCE(pe.razonsocial, CONCAT(pe.apellidos,' ',pe.nombres)) AS cliente,
       dv.garantiameses,
       DATE_ADD(v.fecha, INTERVAL dv.garantiameses MONTH) AS garantiahasta
FROM series s
JOIN detventa dv  ON dv.iddetventa = s.iddetventa
JOIN ventas v     ON v.idventa = dv.idventa
JOIN personas pe  ON pe.idpersona = v.idcliente
JOIN productos pr ON pr.idproducto = s.idproducto;

-- Base para reportes por línea, canal, vendedor, técnico y producto
CREATE VIEW vw_reporte_ventas AS
SELECT v.idventa, DATE(v.fecha) AS fecha, l.nombre AS linea, c.canal,
       v.idvendedor, e.idtecnico, dv.idproducto, pr.nombre AS producto,
       dv.cantidad, dv.total AS importe,
       ROUND(dv.cantidad * dv.costounitario, 2) AS costo
FROM ventas v
JOIN estadoventa ev ON ev.idestado = v.idestado AND ev.estado = 'Emitida'
JOIN lineas l       ON l.idlinea = v.idlinea
JOIN canalesventa c ON c.idcanal = v.idcanal
JOIN detventa dv    ON dv.idventa = v.idventa
JOIN productos pr   ON pr.idproducto = dv.idproducto
LEFT JOIN entregas e ON e.idventa = v.idventa;

-- Monto esperado por apertura y medio de pago (base del arqueo)
CREATE VIEW vw_arqueo_esperado AS
SELECT a.idapertura, tp.idtipopago, tp.tipopago,
       COALESCE(SUM(p.monto),0) + IF(tp.tipopago = 'Efectivo', a.montoinicial, 0) AS montoesperado
FROM aperturacaja a
CROSS JOIN tipospago tp
LEFT JOIN pagoventa p ON p.idapertura = a.idapertura AND p.idtipopago = tp.idtipopago
GROUP BY a.idapertura, tp.idtipopago, tp.tipopago, a.montoinicial;


