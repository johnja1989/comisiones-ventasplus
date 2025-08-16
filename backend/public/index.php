<?php
/**
 * Punto de entrada de la aplicación
 * Sistema de Comisiones VentasPlus S.A.
 */

// Configuración de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Zona horaria
date_default_timezone_set('America/Bogota');

// Autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Dependencias
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use App\Controllers\ComisionController;
use App\Controllers\VendedorController;
use App\Controllers\VentaController;
use App\Controllers\DashboardController;
use App\Middleware\CorsMiddleware;
use App\Middleware\AuthMiddleware;

// Crear aplicación Slim
$app = AppFactory::create();

// Configurar base path si es necesario
$app->setBasePath('/api');

// Middleware global
$app->addErrorMiddleware(true, true, true);

// CORS Middleware
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

// Manejo de OPTIONS
$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});

// =====================================================
// RUTAS API
// =====================================================

// Rutas públicas
$app->get('/', function ($request, $response) {
    $response->getBody()->write(json_encode([
        'name' => 'API Sistema de Comisiones',
        'version' => '1.0.0',
        'status' => 'active',
        'endpoints' => [
            'comisiones' => '/api/comisiones',
            'vendedores' => '/api/vendedores',
            'ventas' => '/api/ventas',
            'dashboard' => '/api/dashboard'
        ]
    ]));
    return $response->withHeader('Content-Type', 'application/json');
});

// Grupo de rutas para Comisiones
$app->group('/comisiones', function (RouteCollectorProxy $group) {
    $controller = new ComisionController();
    
    $group->get('', [$controller, 'index']);
    $group->get('/{id}', [$controller, 'show']);
    $group->post('/calcular', [$controller, 'calcular']);
    $group->put('/{id}/aprobar', [$controller, 'aprobar']);
    $group->put('/aprobar-lote', [$controller, 'aprobarLote']);
    $group->put('/{id}/pagar', [$controller, 'pagar']);
    $group->get('/reporte', [$controller, 'reporte']);
    $group->get('/exportar', [$controller, 'exportar']);
    $group->get('/dashboard', [$controller, 'dashboard']);
    $group->get('/top-vendedores', [$controller, 'topVendedores']);
    $group->get('/tendencia', [$controller, 'tendencia']);
    $group->post('/recalcular', [$controller, 'recalcular']);
});

// Grupo de rutas para Vendedores
$app->group('/vendedores', function (RouteCollectorProxy $group) {
    $controller = new VendedorController();
    
    $group->get('', [$controller, 'index']);
    $group->get('/{id}', [$controller, 'show']);
    $group->post('', [$controller, 'create']);
    $group->put('/{id}', [$controller, 'update']);
    $group->delete('/{id}', [$controller, 'delete']);
    $group->get('/{id}/ventas', [$controller, 'ventas']);
    $group->get('/{id}/comisiones', [$controller, 'comisiones']);
    $group->get('/ranking', [$controller, 'ranking']);
    $group->get('/search', [$controller, 'search']);
});

// Grupo de rutas para Ventas
$app->group('/ventas', function (RouteCollectorProxy $group) {
    $controller = new VentaController();
    
    $group->get('', [$controller, 'index']);
    $group->get('/{id}', [$controller, 'show']);
    $group->post('', [$controller, 'create']);
    $group->post('/importar', [$controller, 'importar']);
    $group->get('/resumen', [$controller, 'resumen']);
    $group->get('/productos-vendidos', [$controller, 'productosVendidos']);
});

// Grupo de rutas para Dashboard
$app->group('/dashboard', function (RouteCollectorProxy $group) {
    $controller = new DashboardController();
    
    $group->get('/kpis', [$controller, 'kpis']);
    $group->get('/metricas', [$controller, 'metricas']);
    $group->get('/resumen-periodo', [$controller, 'resumenPeriodo']);
    $group->get('/comparativa', [$controller, 'comparativa']);
});

// Manejo de errores 404
$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function ($request, $response) {
    $response->getBody()->write(json_encode([
        'error' => true,
        'message' => 'Endpoint no encontrado'
    ]));
    return $response
        ->withStatus(404)
        ->withHeader('Content-Type', 'application/json');
});

// Ejecutar aplicación
$app->run();
?>