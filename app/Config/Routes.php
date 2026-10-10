<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', fn() => redirect()->to('/login'));
$routes->get('login', 'Auth::login');
$routes->post('login', 'Auth::autenticar');
$routes->get('logout', 'Auth::logout', ['filter' => 'auth']);
$routes->get('dashboard', 'Dashboard::index', ['filter' => 'auth']);

$routes->group('categorias', ['filter' => 'auth'], static function ($routes) {
    $routes->get('/', 'CategoriaController::index');
    $routes->post('guardar', 'CategoriaController::guardar');
    $routes->get('eliminar/(:num)', 'CategoriaController::eliminar/$1');
});

$routes->group('marcas', [' filter' => 'auth'], static function ($routes) {
    $routes->get('/', 'MarcaController::index');
    $routes->post('guardar', 'MarcaController::guardar');
    $routes->get('eliminar/(:num)', 'MarcaController::eliminar/$1');
});

$routes->group('productos', ['filter' => 'auth'], static function($routes) {
    $routes->get('/', 'ProductoController::index');
    $routes->post('guardar', 'ProductoController::guardar');
    $routes->get('cambiarestado/(:num)', 'ProductoController::cambiarEstado/$1');
});

$routes->group('locales', ['filter' => 'auth'], static function($routes) {
    $routes->get('/', 'LocalController::index');
    $routes->post('guardar', 'LocalController::guardar');
    $routes->get('eliminar/(:num)', 'LocalController::eliminar/$1');
});

$routes->group('usuarios', ['filter' => 'auth'], static function($routes) {
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

$routes->group('proveedores',['filter' => 'auth'],function($routes){
    $routes->get('/','ProveedorController::index');
    $routes->post('guardar', 'ProveedorController::guardar');
    $routes->get('eliminar/(:num)', 'ProveedorController::eliminar/$1');
});

$routes->group('kardex',['filter' => 'auth'],function($routes){
    $routes->get('', 'KardexController::index');
});

