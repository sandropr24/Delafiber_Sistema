<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->get('/', fn() => redirect()->to('/admin/login'));


$routes->group('admin', ['namespace' => 'App\Controllers\Admin'], static function ($routes) {

    $routes->get('login', 'Auth::login');
    $routes->post('login', 'Auth::autenticar');
    $routes->get('logout', 'Auth::logout', ['filter' => 'auth']);

    $routes->get('dashboard', 'Dashboard::index', ['filter' => 'auth:Administrador,Operador,Vendedor']);

    $routes->group('locales', ['filter' => 'auth:Administrador'], static function ($routes) {
        $routes->get('/', 'LocalController::index');
        $routes->post('guardar', 'LocalController::guardar');
        $routes->post('eliminar/(:num)', 'LocalController::eliminar/$1');
    });

    $routes->group('tecnicos', ['filter' => 'auth:Administrador'], static function ($routes) {
        $routes->get('', 'TecnicoController::index');
        $routes->post('guardar', 'TecnicoController::guardar');
        $routes->post('cambiar-estado/(:num)', 'TecnicoController::cambiarEstado/$1');
    });

    $routes->group('usuarios', ['filter' => 'auth:Administrador'], static function ($routes) {
        $routes->get('/', 'UsuarioController::index');
        $routes->post('guardar', 'UsuarioController::guardar');
        $routes->post('cambiarestado/(:num)', 'UsuarioController::cambiarEstado/$1');
    });

    $routes->group('servicios', ['filter' => 'auth:Administrador,Operador'], static function ($routes) {
        $routes->get('', 'ServicioController::index');
        $routes->get('crear', 'ServicioController::crear');
        $routes->post('guardar', 'ServicioController::guardar');
        $routes->get('agenda', 'ServicioController::agenda');
        $routes->get('detalles/(:num)', 'ServicioController::detalles/$1');
        $routes->post('reprogramar/(:num)', 'ServicioController::reprogramar/$1');
        $routes->post('cambiar-estado/(:num)', 'ServicioController::cambiarEstado/$1');
        $routes->post('registrar-material/(:num)', 'ServicioController::registrarMaterial/$1');
    });

    $routes->group('proveedores', ['filter' => 'auth:Administrador,Operador'], static function ($routes) {
        $routes->get('/', 'ProveedorController::index');
        $routes->post('guardar', 'ProveedorController::guardar');
        $routes->post('eliminar/(:num)', 'ProveedorController::eliminar/$1');
    });

    $routes->group('cotizaciones', ['filter' => 'auth:Administrador,Operador,Vendedor'], static function ($routes) {
        $routes->get('/', 'CotizacionController::index');
        $routes->get('crear', 'CotizacionController::crear');
        $routes->get('editar/(:num)', 'CotizacionController::editar/$1');
        $routes->post('guardar', 'CotizacionController::guardar');
        $routes->get('detalles/(:num)', 'CotizacionController::detalles/$1');
        $routes->post('cambiar-estado/(:num)', 'CotizacionController::cambiarEstado/$1');
        $routes->post('convertir-pedido/(:num)', 'CotizacionController::convertirPedido/$1', ['filter' => 'auth:Administrador,Vendedor']);
        $routes->get('pdf/(:num)', 'CotizacionController::pdf/$1');
        $routes->get('series', 'CotizacionController::series', ['filter' => 'auth:Administrador']);
        $routes->post('guardar-serie', 'CotizacionController::guardarSerie', ['filter' => 'auth:Administrador']);
    });

    $routes->group('compras', ['filter' => 'auth:Administrador,Operador'], static function ($routes) {
        $routes->get('', 'CompraController::index');
        $routes->get('nuevo', 'CompraController::nuevo');
        $routes->post('guardar', 'CompraController::guardar');
        $routes->get('detalles/(:num)', 'CompraController::detalles/$1');
    });

    $routes->group('clientes', ['filter' => 'auth:Administrador,Vendedor'], static function ($routes) {
        $routes->get('/', 'ClienteController::index');
        $routes->post('guardar', 'ClienteController::guardar');
        $routes->post('cambiarestado/(:num)', 'ClienteController::cambiarEstado/$1');
    });
    $routes->post('clientes/guardar-express', 'VentaController::guardarClienteExpress', ['filter' => 'auth:Administrador,Vendedor']);

    $routes->group('ventas', ['filter' => 'auth:Administrador,Vendedor'], static function ($routes) {
        $routes->get('', 'VentaController::index');
        $routes->get('crear', 'VentaController::crear');
        $routes->post('guardar', 'VentaController::guardar');
        $routes->get('detalles/(:num)', 'VentaController::detalles/$1');
        $routes->get('nota-pdf/(:num)', 'NotaVentaPdfController::generar/$1');
        $routes->post('solicitar-anulacion/(:num)', 'VentaAnulacionController::solicitar/$1');
        $routes->post('revisar-anulacion/(:num)', 'VentaAnulacionController::revisar/$1', ['filter' => 'auth:Administrador']);
        $routes->post('ejecutar-anulacion/(:num)', 'VentaAnulacionController::ejecutar/$1', ['filter' => 'auth:Administrador']);
    });

    $routes->group('pedidos', ['filter' => 'auth:Administrador,Vendedor'], static function ($routes) {
        $routes->get('/', 'PedidoController::index');
        $routes->get('crear', 'PedidoController::crear');
        $routes->get('editar/(:num)', 'PedidoController::editar/$1');
        $routes->post('guardar', 'PedidoController::guardar');
        $routes->get('detalles/(:num)', 'PedidoController::detalles/$1');
        $routes->post('cambiar-estado/(:num)', 'PedidoController::cambiarEstado/$1');
    });

    $routes->group('catalogos', ['filter' => 'auth:Administrador,Operador,Vendedor'], static function ($routes) {
        $routes->get('/', 'CatalogoController::index');
        $routes->get('buscarCategorias', 'CatalogoController::buscarCategorias');
        $routes->get('buscarMarcas', 'CatalogoController::buscarMarcas');
    });

    $routes->group('catalogos', ['filter' => 'auth:Administrador'], static function ($routes) {
        $routes->post('guardar-categoria', 'CatalogoController::guardarCategoria');
        $routes->post('guardar-marca', 'CatalogoController::guardarMarca');
        $routes->post('cambiar-estado-categoria/(:num)', 'CatalogoController::cambiarEstadoCategoria/$1');
        $routes->post('cambiar-estado-marca/(:num)', 'CatalogoController::cambiarEstadoMarca/$1');
    });

    $routes->group('productos', ['filter' => 'auth:Administrador,Operador,Vendedor'], static function ($routes) {
        $routes->get('buscar_tabla', 'ProductoController::buscar_tabla');
        $routes->get('/', 'ProductoController::index');
        $routes->post('guardar', 'ProductoController::guardar', ['filter' => 'auth:Administrador']);
        $routes->post('cambiarestado/(:num)', 'ProductoController::cambiarEstado/$1', ['filter' => 'auth:Administrador']);
    });

    $routes->group('kardex', ['filter' => 'auth:Administrador,Operador,Vendedor'], static function ($routes) {
     $routes->get('buscar_tabla', 'KardexController::buscar_tabla');
        $routes->get('', 'KardexController::index');
        $routes->get('historial/(:num)', 'KardexController::historial/$1');
    });

    $routes->group('entregas', ['filter' => 'auth:Administrador,Operador,Vendedor'], static function ($routes) {
        $routes->get('/', 'EntregaController::index');
        $routes->get('buscar_tabla', 'EntregaController::buscar_tabla');
        $routes->get('token-csrf', 'EntregaController::token_csrf');
        $routes->post('cambiar_estado', 'EntregaController::cambiar_estado');
        $routes->post('registrar-incidencia', 'EntregaController::registrar_incidencia');
        $routes->post('cancelar-definitivamente', 'EntregaController::cancelar_definitivamente');
        $routes->get('historial-incidencias/(:num)', 'EntregaController::historial_incidencias/$1');
    });
});
