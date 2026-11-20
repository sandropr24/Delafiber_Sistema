# CodeIgniter 4 Application Starter

## What is CodeIgniter?

CodeIgniter is a PHP full-stack web framework that is light, fast, flexible and secure.
More information can be found at the [official site](https://codeigniter.com).

This repository holds a composer-installable app starter.
It has been built from the
[development repository](https://github.com/codeigniter4/CodeIgniter4).

More information about the plans for version 4 can be found in [CodeIgniter 4](https://forum.codeigniter.com/forumdisplay.php?fid=28) on the forums.

You can read the [user guide](https://codeigniter.com/user_guide/)
corresponding to the latest version of the framework.

## Installation & updates

`composer create-project codeigniter4/appstarter` then `composer update` whenever
there is a new release of the framework.

When updating, check the release notes to see if there are any changes you might need to apply
to your `app` folder. The affected files can be copied or merged from
`vendor/codeigniter4/framework/app`.

## Setup

Copy `env` to `.env` and tailor for your app, specifically the baseURL
and any database settings.

### Initial roles and permissions

For a **new, empty database only**, apply all migrations before running `MainSeeder`:

```bash
php spark migrate
php spark db:seed MainSeeder
```

Before seeding, set a unique temporary `seed.adminPassword` (12 to 72 characters)
in `.env`, then remove it after the initial account has been created.
Configure también `seed.adminUser` y `seed.adminDoc`; la persona con ese documento
debe existir previamente. No hay usuario ni documento predeterminados en UsuarioSeeder.
`MainSeeder` initializes security first and creates the initial administrator
after the local locations exist, assigning those locations only to the new account.

`SeguridadSeeder` can be run separately and repeatedly to initialize missing roles
and the permission catalog. New Administrador roles receive the four service and
inventory permissions; new Operador roles receive all except consumption reversal;
new Vendedor roles receive none of these permissions.
`RolSeeder` remains available as an alias to this initialization.

Existing roles retain their grants, including intentionally empty permission sets.
Existing users retain their passwords, state, role and location assignments.
This is not an automatic repair of historical missing grants: review and explicitly
authorize those changes separately. Do not rerun `MainSeeder` on an existing database;
other demo-data seeders are not idempotent.

## Cash sessions and payment vouchers

New sales now require an open cash session belonging to the cashier and sale
location. Before testing, an administrator must visit `/admin/caja/configuracion`,
complete missing payment methods, create a cash point, and register destination
accounts. Sellers need their locations assigned in Users. Open the session from
`/admin/caja` before recording a sale.

This module uses the existing schema; no new migration or automatic historical
payment reassignment is needed. Electronic payments require an active PEN account,
an operation reference and a private image voucher. See `tests/README.md` for setup,
upload limits, cash closing, supported scope and manual checks.

## Garantías de equipos

Aplicar las migraciones antes de usar esta versión de ventas y garantías:

```bash
php spark migrate
```

No repetir `MainSeeder` sobre una base existente. El módulo está en
`/admin/garantias` y en el menú **Garantías**. Administrador y Operador registran
y gestionan casos; Vendedor solo consulta. La recepción exige una venta no
anulada, entrega confirmada y garantía vigente desde la fecha de venta.

Las ventas nuevas conservan el nombre, tipo, control de series y plazo vendido;
los combos conservan el plazo de cada componente, no el plazo del kit.
Los detalles antiguos cuyo plazo se perdió y los componentes históricos sin
plazo requieren verificación explícita por un administrador contra el documento
original, con observación de respaldo. No se rellenan garantías antiguas desde
el catálogo actual ni se modifica la venta histórica.

El seguimiento guarda estados, fechas, usuario, proveedor y observaciones.
No modifica stock, series ni pagos automáticamente: un reemplazo físico requiere
su proceso de inventario autorizado. Para artículos sin serie se admite un caso
abierto por artículo y detalle de venta; para equipos seriados, uno por serie.
Consultar `tests/README.md` para las pruebas y el recorrido manual.

## Order email notifications

Web customers receive plain-text email when an order is created, confirmed or
cancelled. The creation message contains the private tracking link. Configure the
sender and SMTP transport only through `.env`:

```ini
email.fromEmail = 'pedidos@example.com'
email.fromName = 'DELAFIBER'
email.protocol = 'smtp'
email.SMTPHost = 'smtp.example.com'
email.SMTPUser = 'pedidos@example.com'
email.SMTPPass = 'use-an-environment-secret'
email.SMTPPort = 587
email.SMTPCrypto = 'tls'
email.mailType = 'text'
```

An email failure never rolls back an order or status change. The customer is told
to preserve the on-screen tracking link and administrators receive a warning to
contact the customer manually. Never log tracking tokens or SMTP credentials.

## Production security

The web server document root must point to `public/`, never to the repository root.
Use at least the following production settings in `.env`:

```ini
CI_ENVIRONMENT = production
app.baseURL = 'https://example.com/'
app.forceGlobalSecureRequests = true
cookie.secure = true
```

Use a dedicated database user with only the permissions required by the application.
El filtro de arranque devuelve 500 y registra un error crítico si en producción
falta cualquiera de los tres ajustes HTTPS anteriores, incluso antes de redirigir.

### Empaquetado y secretos

Excluir del paquete de código: `.env`, `vendor/`, `build/`, `writable/*` y
`public/uploads/*`. Instalar dependencias con Composer en destino y preparar los
directorios escribibles requeridos. Los archivos de negocio (vouchers, fotos,
etc.) se respaldan/trasladan por separado y con acceso restringido; no eliminarlos.
No incluir sesiones, logs ni cachés de la máquina de desarrollo.

**Acción manual pendiente del propietario:** rotar la contraseña del administrador.
Retirar el secreto del `.env` no cambia la contraseña ya almacenada en la base.
Esta tarea no rota ni publica esa contraseña.

### Límites de la tienda

POST checkout: 5 solicitudes/600 s por IP y 3/3600 s por correo normalizado.
Registro: 5/3600 s por IP. Agregar y actualizar carrito: 30/60 s por IP y acción.
Se usa el Throttler de CI4 (token bucket, con reposición gradual), con claves
hasheadas. AJAX recibe 429 y Retry-After; formularios reciben 303 con un aviso.
Configurar una caché persistente y compartida entre instancias; el handler dummy
no ofrece protección real. Los límites se aplican antes del controlador y no
generan pedidos ni notificaciones al ser rechazados.

### Identidad y verificación de clientes web

Aplicar `php spark migrate` antes de desplegar el cambio. Los pedidos de invitados
usan `pedido.idpersona = NULL`: conocer un DNI no demuestra identidad. El contacto
original se conserva en `pedido_web`; el personal debe verificarlo y asignar un
cliente desde Editar pedido antes de confirmarlo. No se alteran personas existentes.
El listado y detalle administrativos siguen mostrando el contacto del invitado.

Las cuentas nuevas quedan inactivas hasta verificar su correo. El enlace dura
24 horas, su token se guarda solo como SHA-256 y se consume una vez. Repetir el
registro de una cuenta pendiente reenvía a su correo original sin reemplazar
contraseña ni persona. La respuesta pública es siempre genérica.
Las cuentas históricas no se marcan como verificadas por suposición: sus titulares
deben solicitar el enlace desde Registro antes de volver a entrar. No se reactivan
cuentas deshabilitadas que no tengan una verificación pendiente.
Verificar correo acredita su control, no verifica la titularidad legal del DNI.

### Intentos de acceso y proxies

Los dos logins cuentan solo fallos: 10 por IP y 5 por usuario/correo en 300 s.
Un acceso correcto limpia el contador del usuario, sin consumir cupo de IP.
Una identidad ya bloqueada debe esperar al vencimiento. Las claves de caché son
`login_fail_ip_<sha256>` y `login_fail_user_<sha256>` (ámbito admin/cliente).
No se guardan contraseñas en el old input de la sesión.

Detrás de un proxy, definir `app.proxyIPs` como lista de IPs/CIDRs de proxies
realmente confiables, separada por comas; se utiliza X-Forwarded-For. No usar una
lista universal ni confiar en encabezados directos de Internet. El proxy debe
sobrescribir esos encabezados. En varias instancias usar caché compartida; queda
pendiente validar concurrencia real del backend de caché elegido en producción.
Do not deploy local files from `writable/session`, `writable/debugbar`, or `writable/logs`.
`UsuarioSeeder` requires a temporary `seed.adminPassword` value of 12 to 72 characters;
remove that value after seeding and rotate any account previously created with a shared password.

## Important Change with index.php

`index.php` is no longer in the root of the project! It has been moved inside the *public* folder,
for better security and separation of components.

This means that you should configure your web server to "point" to your project's *public* folder, and
not to the project root. A better practice would be to configure a virtual host to point there. A poor practice would be to point your web server to the project root and expect to enter *public/...*, as the rest of your logic and the
framework are exposed.

**Please** read the user guide for a better explanation of how CI4 works!

## Repository Management

We use GitHub issues, in our main repository, to track **BUGS** and to track approved **DEVELOPMENT** work packages.
We use our [forum](http://forum.codeigniter.com) to provide SUPPORT and to discuss
FEATURE REQUESTS.

This repository is a "distribution" one, built by our release preparation script.
Problems with it can be raised on our forum, or as issues in the main repository.

## Server Requirements

PHP version 8.2 or higher is required, with the following extensions installed:

- [intl](http://php.net/manual/en/intl.requirements.php)
- [mbstring](http://php.net/manual/en/mbstring.installation.php)

> [!WARNING]
> - The end of life date for PHP 7.4 was November 28, 2022.
> - The end of life date for PHP 8.0 was November 26, 2023.
> - The end of life date for PHP 8.1 was December 31, 2025.
> - If you are still using below PHP 8.2, you should upgrade immediately.
> - The end of life date for PHP 8.2 will be December 31, 2026.

Additionally, make sure that the following extensions are enabled in your PHP:

- json (enabled by default - don't turn it off)
- [mysqlnd](http://php.net/manual/en/mysqlnd.install.php) if you plan to use MySQL
- [libcurl](http://php.net/manual/en/curl.requirements.php) if you plan to use the HTTP\CURLRequest library
