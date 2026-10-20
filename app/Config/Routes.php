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
    $routes->post('actualizar-categoria', 'CatalogoController::actualizarCategoria');
    $routes->post('actualizar-marca', 'CatalogoController::actualizarMarca');
    $routes->get('eliminar-categoria/(:num)', 'CatalogoController::eliminarCategoria/$1');
    $routes->get('eliminar-marca/(:num)', 'CatalogoController::eliminarMarca/$1');
});

$routes->group('productos', ['filter' => 'auth'], static function ($routes) {
    $routes->get('buscar_tabla', 'ProductoController::buscar_tabla');
    $routes->get('/', 'ProductoController::index');
    $routes->post('guardar', 'ProductoController::guardar');
    $routes->get('cambiarestado/(:num)', 'ProductoController::cambiarEstado/$1');
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
    $routes->get('eliminar/(:num)', 'UsuarioController::eliminar/$1');
});

$routes->group('clientes', ['filter' => 'auth'], static function ($routes) {
    $routes->get('/', 'ClienteController::index');
    $routes->post('guardar', 'ClienteController::guardar');
    $routes->get('eliminar/(:num)', 'ClienteController::eliminar/$1');
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

$routes->post('clientes/guardar-express', 'VentaController::guardarClienteExpress');
$routes->group('ventas', static function ($routes) {
    $routes->get('', 'VentaController::index');
    $routes->get('crear', 'VentaController::crear');
    $routes->post('guardar', 'VentaController::guardar');
    $routes->get('detalles/(:num)', 'VentaController::detalles/$1');
});
