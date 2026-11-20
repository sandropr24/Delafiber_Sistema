# Pruebas de regresión

## Agregar al carrito sin recargar

```bash
php vendor/bin/phpunit tests/unit/TiendaCarritoAgregarTest.php tests/unit/TiendaCarritoControllerTest.php tests/unit/TiendaCatalogoViewsTest.php --no-coverage --no-logging --do-not-cache-result
node --test tests/javascript/tienda-carrito.test.cjs
```

Catálogo y ficha usan el mismo POST de siempre. Solo las peticiones AJAX reciben
JSON; sin JavaScript o sin Fetch/FormData se conserva la redirección al carrito.
El servidor recalcula cantidades e importes con el catálogo vigente y publica
solo nombre, unidad, cantidades y total referencial, nunca costos ni series.
El contador representa artículos distintos: agregar dos veces el mismo equipo
incrementa su cantidad, no el número de tipos de artículo.

Las solicitudes se procesan de una en una desde la página para evitar dobles
clics y conflictos de CSRF. Cada respuesta actualiza los tokens de todos los
formularios. Ante validaciones se permite corregir; ante token vencido,
redirección inesperada o una respuesta perdida se exige revisar/recargar.
No se reenvía automáticamente un POST cuyo resultado sea incierto.
Los navegadores con AbortSignal.timeout limitan la espera a 20 segundos.

Las pruebas PHP ejecutan servicio y controlador reales con transporte SQL
simulado, sin base de trabajo. Las JavaScript simulan respuestas y DOM:

1. Desde catálogo, agregar y seguir comprando: no debe cambiar la URL,
   el orden, los filtros ni el desplazamiento.
2. Agregar el mismo artículo dos veces y otro distinto; comprobar contador,
   cantidades y total en el carrito.
3. Desde ficha, probar cantidades enteras y cable por metros. Una fracción
   de equipo o servicio debe rechazarse sin cambiar la selección anterior.
4. Probar doble clic, una pestaña con token antiguo y desconexión. Nunca
   debe reenviar por sí solo la solicitud ni afirmar que un agregado incierto
   falló definitivamente.
5. Con teclado, cerrar el aviso o pulsar Seguir comprando y comprobar el foco.
   El aviso no desaparece por un temporizador ni atrapa el foco.
6. Desactivar JavaScript: agregar debe seguir llevando al carrito. No confirmar
   un checkout real para estas comprobaciones.

## Catálogo y fichas de la tienda

```bash
php vendor/bin/phpunit tests/unit/FiltrosTiendaTest.php tests/unit/TiendaCatalogoViewsTest.php --no-coverage --no-logging --do-not-cache-result
php vendor/bin/phpunit tests/integration/TiendaCatalogoFiltrosTest.php --no-coverage --no-logging --do-not-cache-result
node --test tests/javascript/tienda.test.cjs
```

Los filtros combinan búsqueda (incluida marca), categoría y subcategorías, marca
y precios referenciales inclusivos en PEN. El orden usa exclusivamente una lista
permitida y un desempate estable por identificador. Parámetros malformados no
se convierten en SQL; precios inválidos o rangos invertidos muestran un aviso.
La paginación y la búsqueda de cabecera conservan los filtros.

Las fichas usan la foto ya registrada, con ampliación accesible y enlace de
respaldo sin JavaScript. No se agregan fotos ni especificaciones inventadas.
Los combos muestran únicamente componentes publicados, con cantidades por kit
y garantías individuales; no se exponen costos, stock exacto ni series.
El pedido continúa sin pago en línea ni reserva automática de inventario.

Las pruebas de filtros y vistas no conectan a la base de trabajo. La integración
usa SQLite en memoria y se omite si falta la extensión. Las pruebas JavaScript
simulan el DOM y no sustituyen una comprobación en navegador:

1. Combinar marca y rango de precios; ordenar por precio en ambas direcciones.
2. Cambiar de página y quitar un filtro: deben conservarse los demás.
3. Buscar por una marca desde la cabecera con otros filtros activos.
4. Abrir producto, servicio, cable por metros y combo; comprobar cantidades,
   plazos individuales y la ausencia de costos o series.
5. Con foto registrada, ampliar, cerrar con Escape y comprobar el retorno del
   foco; sin JavaScript, el enlace debe abrir la imagen.
6. Probar móvil y escritorio. En checkout, verificar entrega sin costo con
   cobertura y horario sujetos a coordinación, sin registrar un pedido real.

## Clientes y RUC

Ejecutar desde la raíz del proyecto:

```bash
php vendor/bin/phpunit tests/unit tests/session --no-coverage --do-not-cache-result
node --test tests/javascript/clientes.test.cjs
```

Las pruebas de `PersonaModelTest` comprueban normalización, validaciones,
compatibilidad con usuarios y protección de campos al insertar/editar.
Sustituyen únicamente la escritura SQL y no modifican la base de datos.
Las pruebas JavaScript verifican el comportamiento del formulario con un DOM
simulado; no sustituyen una prueba en navegador.

La suite completa se ejecuta con:

```bash
php vendor/bin/phpunit --no-coverage --do-not-cache-result
```

Las pruebas de base de datos e integración requieren la extensión SQLite3 en
PHP CLI. No deben apuntarse a la base de datos de trabajo.

## Comprobación manual en un entorno de pruebas

1. En Clientes, crear un RUC de 11 dígitos y una razón social con números,
   sin nombres ni apellidos. Comprobar que aparece en el listado.
2. Editar la empresa y verificar que conserva su identificador y estado.
3. Abrir un RUC antiguo guardado en `nombres`: el formulario debe recuperar
   ese valor como razón social, sin cambiar datos hasta guardar.
4. Crear un DNI con nombres y apellidos; comprobar que los campos obligatorios
   cambian al alternar entre DNI y RUC.
5. Provocar un error de validación y comprobar que aparecen los errores y
   se recuperan los datos del formulario.
6. Desde Nueva venta, registrar un RUC en Cliente rápido y comprobar que queda
   seleccionado con su razón social, también después de recargar la página.

## Protección de productos con operaciones pendientes

```bash
php vendor/bin/phpunit tests/unit/ProductoConfiguracionServiceTest.php --no-coverage --do-not-cache-result
php vendor/bin/phpunit tests/integration/ProductoConfiguracionDatabaseTest.php --no-coverage --do-not-cache-result
```

Las pruebas unitarias comprueban bloqueos, parámetros y decisiones sin ejecutar
SQL. Las pruebas de integración usan SQLite en memoria y se omiten explícitamente
si falta la extensión. Ninguna de ellas utiliza la base de datos de trabajo.
Los bloqueos concurrentes de MySQL deben comprobarse en una base de pruebas MySQL.

Comprobación manual, como administrador y con datos de prueba:

1. Registrar una venta con un producto físico con stock disponible y dejar
   la entrega pendiente.
2. En Productos, editar ese artículo e intentar cambiarlo a servicio, quitar
   el control de stock o cambiar el control de series. Debe impedir el guardado
   y explicar la operación pendiente, sin modificar reservas ni series.
   Si tiene series, desactivar ambos controles permite probar el bloqueo sin
   que antes intervenga la regla «un artículo con serie debe controlar stock».
3. Manteniendo los controles originales, cambiar solo nombre o descripción
   (o subir otra imagen). Debe permitir guardar sin alterar la reserva.
4. Repetir con un producto vendido como componente de un combo y con un
   artículo vendido sin stock: en este último caso no debe permitir activarlo
   mientras su entrega esté pendiente.
5. Cancelar únicamente la entrega no debe desbloquear la configuración:
   la venta requiere su resolución comercial y la liberación de reservas.
6. Con la entrega completada y las reservas consumidas, o con la venta anulada
   comercialmente y las reservas liberadas, esos cambios vuelven a estar
   permitidos. Se mantiene la regla anterior que impide convertir un combo
   existente en otro tipo.
7. Para comprobar concurrencia, intentar editar la configuración y registrar
   una venta del mismo artículo desde dos sesiones. Si la venta se confirma
   primero, la edición debe detectar su operación pendiente; si se edita
   primero, la venta debe validar la configuración nueva o pedir reintentar
   si cambió la composición de un combo.

## Entrega del pedido web al convertir en venta

```bash
php vendor/bin/phpunit tests/unit/EntregaPedidoWebTest.php tests/unit/PedidoDatosWebTest.php --no-coverage --do-not-cache-result
node --test tests/javascript/entrega-web.test.cjs
```

Estas pruebas comprueban la propuesta de entrega, los límites de campos,
la conservación de la solicitud, la selección de datos sin tokens y la
auditoría de ajustes. Las consultas/escrituras SQL del modelo y el DOM están
simulados; no sustituyen una prueba completa con MySQL y navegador.

Comprobación manual con datos de prueba:

1. Desde la tienda, enviar un pedido a domicilio y anotar su código.
   Usar una dirección de hasta 150 caracteres para este primer caso.
2. En Administración → Pedidos, abrir ese código: debe mostrar modalidad,
   contacto, correo, teléfono original, dirección e indicaciones.
3. Confirmar el pedido y pulsar «Convertir a venta». Debe seleccionar
   domicilio y precargar la dirección y el teléfono. Los separadores de un
   teléfono local de 9 dígitos y el prefijo explícito `+51` se normalizan solo
   en la propuesta; el teléfono original sigue visible sin cambios.
4. Modificar dirección o teléfono y actualizar el navegador: debe recuperar
   los ajustes del borrador. Tras un error del servidor también debe
   conservarlos. «Recargar pedido» es una acción explícita que descarta el
   borrador y vuelve a la propuesta original.
5. Completar los datos y el pago de una venta de prueba. Verificar la entrega
   guardada y el historial «Ajustes al convertir a venta». La solicitud web
   debe conservar los datos anteriores.
6. Repetir con recojo: debe seleccionar «Recojo en tienda» sin exigir dirección
   ni teléfono de entrega.
7. Si un pedido existente tiene una dirección de más de 150 caracteres o un
   teléfono de formato incompatible, debe mostrar aviso y exigir revisión,
   sin truncar la solicitud original. Cambiar a recojo desactiva esos campos.
8. Comprobar que ventas directas, pedidos administrativos y cotizaciones
   mantienen su comportamiento y no muestran una solicitud web inexistente.

La integración no crea pagos ni cambia precios, descuentos o estados por sí
sola. El vendedor sigue revisando y registrando la venta de la forma habitual.

## Inicialización de roles, permisos y administrador

```bash
php vendor/bin/phpunit tests/unit/SeguridadInicialTest.php --no-coverage --do-not-cache-result
php vendor/bin/phpunit tests/integration/SeguridadInicialDatabaseTest.php --no-coverage --do-not-cache-result
```

Las pruebas unitarias ejecutan el servicio y los seeders con conexión, builders
y transacciones simulados. Comprueban los permisos de roles nuevos, el orden de
inicialización, la ausencia de duplicados, la conservación de autorizaciones y
asignaciones existentes, y la reversión de escrituras si hay errores.
La contraseña utilizada en las pruebas solo existe temporalmente en el proceso.

Las pruebas de integración usan tablas aisladas en SQLite en memoria y consultan
el `PermissionService` real. Se omiten si PHP CLI no tiene SQLite3. Ninguna prueba
ejecuta seeders ni migraciones sobre la base de datos de trabajo.

Comprobación manual **en una base nueva y descartable**, nunca sobre datos reales:

1. Ejecutar todas las migraciones. Configurar una contraseña temporal única
   `seed.adminPassword` de 12 a 72 caracteres y ejecutar `MainSeeder` una sola vez.
   Retirar después esa contraseña de `.env`.
2. Verificar que el administrador inicial puede abrir Servicios Técnicos
   y que tiene locales asignados en Usuarios.
3. Comprobar que un Operador nuevo puede ver, gestionar y consumir materiales,
   pero no anular consumos. Un Vendedor no recibe permisos de servicios.
   Los locales de usuarios creados desde administración se eligen explícitamente.
4. Solo en esa base de prueba, repetir `php spark db:seed SeguridadSeeder`.
   No deben duplicarse roles, permisos ni relaciones. También puede repetirse
   `UsuarioSeeder` sin cambiar la cuenta ya existente ni sus locales.
5. Retirar un permiso o una asignación de local en los datos de prueba y repetir
   esos dos seeders: no deben restablecerlos ni reactivar cuentas deshabilitadas.

Los valores por defecto se asignan exclusivamente a roles recién creados.
Un rol histórico sin permisos se conserva: no es posible distinguir de forma
segura una instalación antigua incompleta de una revocación intencional.
La reparación de permisos históricos requiere revisión y autorización explícita.
No repetir `MainSeeder` para hacer esta comprobación: otros seeders de datos
de demostración no son idempotentes.

## Costos de ventas nuevas, dashboard y redirecciones de anulación

```bash
php vendor/bin/phpunit tests/unit/CostoVentaTest.php tests/unit/VentaCostoRegistroTest.php tests/unit/DashboardVentasTest.php tests/unit/VentaAnulacionRedirectTest.php --no-coverage --do-not-cache-result
```

Reglas del costo:

- Los artículos con stock toman `kardex.costopromedio` del local de la venta,
  leído bajo el mismo `FOR UPDATE` que comprueba y reserva existencias.
  Las compras ya convierten ese costo a PEN; no se aplica otro tipo de cambio.
- Los combos suman los costos de las cantidades de componentes efectivamente
  vendidas y calculan el costo por unidad del combo. No usan el costo del
  catálogo del combo ni aceptan el costo enviado desde el formulario.
- Los artículos sin stock conservan `productos.preciocosto`, o cero si no está
  configurado. No se calculan automáticamente mano de obra, gastos ni utilidad neta.
- Se conserva la precisión de Kardex durante el cálculo y se redondea una vez
  a dos decimales, de acuerdo con la columna existente `detventa.costounitario`.
  Un costo inválido o fuera de rango rechaza la venta, sin escrituras parciales.
- El costo queda fijado al registrar la venta, no al entregar. No se recalculan
  ventas históricas y repetir la misma solicitud no reescribe sus detalles.
  No se requieren migraciones ni seeders para estos cambios.

Las pruebas ejecutan el cálculo, `VentaModel::registrarVenta()` y los controladores
reales, sustituyendo el transporte SQL, las escrituras de modelos y la vista
del dashboard por dobles. No se conectan a la base de datos de trabajo.
Comprueban costos por local, combos con servicios y cable por metros, redondeo,
reversión ante costos inválidos y ausencia de escrituras al repetir una venta.
También verifican los filtros de anulación del dashboard y los destinos de
redirección en validaciones y errores de las tres acciones de anulación.

Comprobación manual **solo en una base de pruebas descartable**:

1. Registrar compras de un mismo equipo en dos locales con costos distintos;
   incluir una compra USD y confirmar su conversión a PEN. Vender el equipo
   desde cada local y revisar `detventa.costounitario`: debe corresponder al
   promedio de ese local al registrar la venta.
2. Vender dos unidades de un combo con equipos, cable e instalación. Comparar
   el costo unitario guardado con la suma de sus componentes por kit. El costo
   del servicio será el configurado; cero no significa que no haya gastos reales.
3. Registrar otra compra después: el costo de la venta anterior debe conservarse.
4. Completar una anulación de prueba por el flujo autorizado: la venta debe dejar
   de sumar en «Ventas de hoy», en el gráfico y en productos más vendidos, y
   no aparecer en las últimas ventas del dashboard. La venta sigue disponible
   en su historial; no se elimina.
5. Revisar solicitud, revisión y ejecución de anulación, incluyendo errores:
   deben regresar a `/admin/ventas` o `/admin/ventas/detalles/{id}` sin un 404.

Estas pruebas no sustituyen una validación operativa con MySQL y navegador.

## Caja y soporte de cobros

El menú **Caja y cobros** está disponible para Administrador y Vendedor.
Este bloque utiliza las tablas existentes: no agrega migraciones y no asigna
pagos históricos a una apertura. **Toda venta nueva requiere caja abierta**.

### Preparación y prueba manual en esta copia

1. Entrar como administrador en `/admin/caja/configuracion`.
2. Usar **Agregar medios faltantes** para completar Efectivo, Yape, Plin, QR,
   Tarjeta, Transferencia y Depósito. La acción es idempotente: no cambia
   identificadores, nombres ni estados de medios existentes.
3. Crear un punto de caja para el local de prueba. Crear las cuentas destino
   que se usarán: BCP, BBVA o Interbank. No se inventan números de cuenta.
   Las ventas actuales son PEN y solo aceptan cuentas PEN activas.
4. Si se prueba como Vendedor, asignarle el local desde Usuarios. El administrador
   puede configurar/operar en todos los locales; cada vendedor solo en los asignados.
5. Abrir caja con el monto inicial en efectivo. Solo puede haber una apertura
   por usuario y una por punto de caja. Si la caja está ocupada por otro usuario,
   debe rechazarse sin duplicar aperturas.
6. Crear una venta al contado y comprobar la apertura seleccionada. Cambiar a
   otro local sin apertura debe impedir cobrar. El servidor comprueba de nuevo
   usuario, local, caja activa y apertura abierta, aunque se altere el formulario.
7. Probar efectivo con vuelto y un pago mixto. Cada pago electrónico requiere
   cuenta destino, operación y foto JPG/PNG/WEBP de hasta 4 MB, con un máximo
   de 12000 píxeles por lado. PHP y el servidor web deben permitir ese tamaño:
   `upload_max_filesize` debe ser al menos 4M, y `post_max_size` debe superar
   la suma de los archivos y el formulario. El formulario admite hasta 20 pagos.
8. Consultar la venta y su apertura: deben mostrar cuenta, operación y voucher.
   Las imágenes se guardan fuera de `public/`, en `writable/uploads/vouchers`,
   con nombres aleatorios y permisos privados. Solo administrador o usuario
   que cobró puede abrir el archivo a través de la ruta autenticada.
9. Recargar el formulario o provocar una validación fallida: las cuentas se
   recuperan del borrador/POST, pero las fotos deben seleccionarse otra vez.
   No se guardan archivos en `sessionStorage`. Si se configura caja/cuentas
   desde otra pestaña, recargar la venta para actualizar las opciones.
10. Entrar al detalle de la apertura y completar el contado de **cada medio**.
    El esperado en efectivo es inicial + montos aplicados, no dinero recibido
    antes de dar vuelto. Si hay diferencias, exigir una observación de al menos
    cinco caracteres. El propietario realiza el cierre normal; un administrador
    puede ejecutar un cierre de contingencia con motivo y trazabilidad.
11. Cerrar caja y comprobar el arqueo guardado. Intentar cobrar con una pestaña
    antigua: debe rechazarse. Volver a abrir permite comenzar otra sesión.
    El administrador ve las aperturas de todos; un vendedor, solo las propias.

### Alcance y conservación del dinero

- El cierre recalcula los cobros bajo bloqueo dentro de la transacción y guarda
  esperado, contado y observación; la base calcula la diferencia.
- Una venta anulada cuyo reembolso está pendiente **no se resta de caja**.
  Cuando el administrador confirma la devolución, se registra un egreso
  inmutable y el saldo pendiente de la anulación se actualiza.
- Se registran aportes, retiros, gastos, ajustes, reembolsos, adelantos,
  contraentregas y cobros posteriores con técnico o cuota opcional. Los reportes de
  día/mes separan cobros, ventas por producto y otros movimientos.
- No se pueden borrar ni sobrescribir cuentas o puntos de caja desde el módulo;
  se activan/desactivan para conservar el historial. Una caja abierta no se desactiva.
- Si una venta se revierte por error, se eliminan solo sus vouchers recién
  creados. Repetir una solicitud ya confirmada no vuelve a cobrar ni subir archivos.
- En producción, el servidor debe publicar únicamente `public/` y permitir que
  el mismo usuario de PHP escriba/lea el directorio privado. No copiar vouchers
  a una carpeta pública para resolver problemas de permisos.

### Pruebas automatizadas

```bash
php vendor/bin/phpunit tests/unit tests/session --no-coverage --do-not-cache-result
node --test tests/javascript/*.test.cjs
php vendor/bin/phpunit tests/integration/CajaDatabaseTest.php --no-coverage --do-not-cache-result
```

Las pruebas unitarias de caja ejecutan los servicios y las transacciones con
conexión y builders simulados. Las de venta ejercitan el registro real,
incluyendo vinculación de apertura/cuenta, rechazo de apertura cerrada y
limpieza de fotos tras fallo. Los vouchers de prueba se crean exclusivamente
en directorios propios de `sys_get_temp_dir()` y se limpian al terminar.
Se prueban además la autorización del controlador de vouchers, las vistas y
la selección de caja/cuenta y recuperación de borradores en JavaScript.

Las tres pruebas SQL de `CajaDatabaseTest` usan SQLite en memoria y se omiten
si falta la extensión. Verifican apertura sin duplicados, cierre con diferencia
calculada y reversión ante error SQL. No validan la concurrencia real de MySQL:
esa comprobación y el recorrido con navegador siguen siendo necesarios.
No se ejecutan seeders ni escrituras sobre la base configurada del proyecto.

## Garantías de equipos y componentes de combos

```bash
php vendor/bin/phpunit tests/unit/GarantiaWorkflowTest.php tests/unit/GarantiaServiceTest.php tests/unit/GarantiaViewsTest.php tests/unit/VentaCostoRegistroTest.php --no-coverage --no-logging --do-not-cache-result
php vendor/bin/phpunit tests/integration/GarantiaDatabaseTest.php --no-coverage --no-logging --do-not-cache-result
```

Las pruebas unitarias ejecutan las reglas, transacciones y vistas reales con
transporte SQL simulado, sin conectar a la base del proyecto. Comprueban fechas
de fin de mes, expiración inclusiva, permisos vigentes, vínculo de serie y
componente, plazos históricos, reintentos, duplicados, cambios desactualizados,
proveedor obligatorio, reversión ante errores y escape HTML/CSRF.
La regresión de ventas comprueba que el modelo ya no descarte `garantiameses`
y que los plazos individuales se guarden con los componentes vendidos.

Las pruebas de integración crean un esquema mínimo aislado en SQLite en memoria.
Se omiten explícitamente si PHP no tiene SQLite3. Comprueban búsqueda por serie,
DNI y comprobante, separación de componentes, seguimiento y reversión SQL.
No sustituyen la ejecución de la migración ni las pruebas concurrentes con MySQL.

Recorrido manual **solo en una base de pruebas con respaldo**:

1. Aplicar `php spark migrate`; no ejecutar otra vez `MainSeeder`.
2. Configurar una cámara con garantía de 12 meses y un combo con esa cámara y
   un servicio. Registrar una venta con series y confirmar su entrega.
3. Cambiar el plazo/nombre del catálogo después de vender. En Garantías,
   buscar por serie, DNI y número completo de nota. Deben aparecer el nombre
   y plazo originales; el servicio no debe ofrecerse como equipo.
4. Registrar una falla: debe abrir un caso Recibido y guardar usuario y hora.
   Reenviar el mismo formulario no debe duplicarlo. Dos formularios distintos
   para la misma serie deben dejar un solo caso abierto.
5. Enviar a proveedor sin seleccionarlo debe rechazarse; con proveedor válido,
   registrar envío, reparación o reemplazo y entrega al cliente. Cada paso
   conserva su observación, proveedor y responsable en el historial.
6. Intentar saltar de Recibido a Entregado, volver atrás, modificar un caso
   cerrado o confirmar desde una pestaña desactualizada: debe rechazarse.
7. Probar venta anulada, entrega pendiente, serie de otro artículo y garantía
   vencida: ninguna debe permitir registrar el caso.
8. Revisar una venta histórica sin plazo fiable. Operador no puede acreditarlo;
   Administrador debe indicar los meses del comprobante original y la evidencia.
   La fecha de venta original se usa para comprobar la vigencia, sin reescribirla.
9. Como Vendedor, consultar casos y verificar que las rutas de creación/cambio
   estén denegadas incluso enviando POST manual. Como usuario desactivado o
   sin sesión tampoco debe poder acceder.
10. Registrar una atención de un artículo sin serie. No se admiten dos casos
    abiertos del mismo artículo y detalle; al cerrar puede registrarse otra
    falla mientras siga vigente. No se identifican unidades individuales sin serie.

Un cambio a Reemplazado es seguimiento documental: no produce movimientos de
Kardex, no sustituye series automáticamente y no registra un nuevo cobro. Registrar
la serie de reemplazo en la observación y resolver la operación física mediante
el proceso de inventario autorizado.
