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
