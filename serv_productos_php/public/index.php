<?php
/**
 * ShopNow Productos Service - PHP Edition
 * Fat-Free Framework powered microservice
 * 
 * This service manages the product catalog and reads/writes
 * to the persistent productos.csv file shared with the Python version.
 */

// Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables (optional - use try-catch to handle missing .env)
try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->safeLoad();
} catch (\Dotenv\Exception\InvalidPathException $e) {
    // .env file doesn't exist - that's OK, use environment variables or defaults
}

// Global header function (define early so controllers can use it)
function setJsonHeaders() {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Content-Type: application/json; charset=utf-8');
}

// Initialize Fat-Free Framework
$f3 = \Base::instance();

// Configuration
$f3->set('DEBUG', getenv('DEBUG') ?: 3);
$f3->set('CACHE', getenv('CACHE') ?: 'folder=tmp/');
$f3->set('JWT_SECRET', getenv('JWT_SECRET') ?: 'default-secret-change-me');
$f3->set('DATA_FILE', getenv('DATA_FILE') ?: '../productos.csv');

// Include controllers
require_once __DIR__ . '/../src/Controllers/AuthController.php';
require_once __DIR__ . '/../src/Controllers/ProductosController.php';
require_once __DIR__ . '/../src/Middleware/AuthMiddleware.php';

// Initialize controllers
$auth = new \ShopNow\Controllers\AuthController($f3);
$productos = new \ShopNow\Controllers\ProductosController($f3);

// ============================================
// ROUTES
// ============================================

// Health check
$f3->route('GET /', function() {
    http_response_code(200);
    echo json_encode([
        'service' => 'Productos (PHP)',
        'status' => 'healthy',
        'version' => '2.0.0-php'
    ]);
});

// Authentication
$f3->route('POST /login', [$auth, 'login']);

// Products endpoints (protected)
$f3->route('GET /productos', [$productos, 'listar']);
$f3->route('POST /productos', [$productos, 'crear']);
$f3->route('GET /productos/@id', [$productos, 'obtener']);
$f3->route('PUT /productos/@id', [$productos, 'actualizar']);
$f3->route('DELETE /productos/@id', [$productos, 'eliminar']);

// Run the framework
$f3->run();
