<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', fn() => redirect()->to('/login'));
$routes->get('login', 'Auth::login');
$routes->post('login', 'Auth::autenticar');
$routes->post('logout', 'Auth::logout', ['filter' => 'auth']);
$routes->get('dashboard', 'Dashboard::index', ['filter' => 'auth']);