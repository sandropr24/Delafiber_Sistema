<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', fn() => redirect()->to('/login'));
$routes->get('login', 'Auth::login');
$routes->post('login', 'Auth::autenticar');
$routes->get('logout', 'Auth::logout', ['filter' => 'auth']);
$routes->get('dashboard', 'Dashboard::index', ['filter' => 'auth']);

$routes->group('catalogos', function ($routes) {
    $routes->get('', 'CatalogoController::index');
    $routes->post('guardar-categoria', 'CatalogoController::guardarCategoria');
    $routes->post('guardar-marca', 'CatalogoController::guardarMarca');
    $routes->get('cambiar-estado-categoria/(:num)', 'CatalogoController::cambiarEstadoCategoria/$1');
    $routes->get('cambiar-estado-marca/(:num)', 'CatalogoController::cambiarEstadoMarca/$1');
    $routes->get('buscarCategorias', 'CatalogoController::buscarCategorias');
    $routes->get('buscarMarcas', 'CatalogoController::buscarMarcas');
});

$routes->group('productos', ['filter' => 'auth'], static function ($routes) {
    $routes->get('buscar_tabla', 'ProductoController::buscar_tabla');
    $routes->get('/', 'ProductoController::index');
    $routes->post('guardar', 'ProductoController::guardar');
    $routes->post('cambiarestado/(:num)', 'ProductoController::cambiarEstado/$1');
});

$routes->group('locales', ['filter' => 'auth'], static function ($routes) {
    $routes->get('/', 'LocalController::index');
    $routes->post('guardar', 'LocalController::guardar');
    $routes->get('eliminar/(:num)', 'LocalController::eliminar/$1');
});

$routes->group('usuarios', ['filter' => 'auth'], static function ($routes) {
    $routes->get('/', 'UsuarioController::index');
    $routes->post('guardar', 'UsuarioController::guardar');
    $routes->get('cambiarestado/(:num)', 'UsuarioController::cambiarEstado/$1');
});

$routes->group('clientes', ['filter' => 'auth'], static function ($routes) {
    $routes->get('/', 'ClienteController::index');
    $routes->post('guardar', 'ClienteController::guardar');
    $routes->get('cambiarestado/(:num)', 'ClienteController::cambiarEstado/$1');
});

$routes->group('proveedores', ['filter' => 'auth'], function ($routes) {
    $routes->get('/', 'ProveedorController::index');
    $routes->post('guardar', 'ProveedorController::guardar');
    $routes->get('eliminar/(:num)', 'ProveedorController::eliminar/$1');
});

$routes->group('kardex', ['filter' => 'auth'], function ($routes) {
    $routes->get('buscar_tabla', 'KardexController::buscar_tabla');
    $routes->get('', 'KardexController::index');
    $routes->get('historial/(:num)', 'KardexController::historial/$1');
});

$routes->group('compras', ['filter' => 'auth'], function ($routes) {
    $routes->get('', 'CompraController::index');
    $routes->get('nuevo', 'CompraController::nuevo');
    $routes->post('guardar', 'CompraController::guardar');
    $routes->get('detalles/(:num)', 'CompraController::detalles/$1');
});

$routes->post('clientes/guardar-express', 'VentaController::guardarClienteExpress', ['filter' => 'auth']);
$routes->group('ventas', ['filter' => 'auth'], static function ($routes) {
    $routes->get('', 'VentaController::index');
    $routes->get('crear', 'VentaController::crear');
    $routes->post('guardar', 'VentaController::guardar');
    $routes->get('detalles/(:num)', 'VentaController::detalles/$1');
    $routes->get('nota-pdf/(:num)', 'NotaVentaPdfController::generar/$1');
    $routes->post('solicitar-anulacion/(:num)', 'VentaAnulacionController::solicitar/$1');
    $routes->post('revisar-anulacion/(:num)', 'VentaAnulacionController::revisar/$1');
    $routes->post('ejecutar-anulacion/(:num)', 'VentaAnulacionController::ejecutar/$1');
});

$routes->group('entregas', ['filter' => 'auth'], function ($routes) {
    $routes->get('/', 'EntregaController::index');
    $routes->get('buscar_tabla', 'EntregaController::buscar_tabla');
    $routes->post('cambiar_estado', 'EntregaController::cambiar_estado');
    $routes->post('registrar-incidencia', 'EntregaController::registrar_incidencia');
    $routes->post('cancelar-definitivamente', 'EntregaController::cancelar_definitivamente');
    $routes->get('historial-incidencias/(:num)', 'EntregaController::historial_incidencias/$1');
});

$routes->group('tecnicos', ['filter' => 'auth'], static function ($routes) {
    $routes->get('', 'TecnicoController::index');
    $routes->post('guardar', 'TecnicoController::guardar');
    $routes->post('cambiar-estado/(:num)', 'TecnicoController::cambiarEstado/$1');
});

$routes->group('servicios', ['filter' => 'auth'], static function ($routes) {
    $routes->get('', 'ServicioController::index');
    $routes->get('crear', 'ServicioController::crear');
    $routes->post('guardar', 'ServicioController::guardar');
    $routes->get('agenda', 'ServicioController::agenda');
    $routes->get('detalles/(:num)', 'ServicioController::detalles/$1');
    $routes->post('reprogramar/(:num)', 'ServicioController::reprogramar/$1');
    $routes->post('cambiar-estado/(:num)', 'ServicioController::cambiarEstado/$1');
    $routes->post('registrar-material/(:num)', 'ServicioController::registrarMaterial/$1');
});
