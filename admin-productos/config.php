<?php
/**
 * Configuration file for Admin Productos Interface
 * Decoupled UI for the Productos microservice
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Determine the base path for the application
// This allows the app to work from any subdirectory like /admin-productos/
$scriptPath = dirname($_SERVER['SCRIPT_NAME']);
$basePath = ($scriptPath === '/' || $scriptPath === '\\') ? '/' : rtrim($scriptPath, '/') . '/';
define('BASE_PATH', $basePath);

// API Configuration - Auto-detect environment
// Use local development URL if running on localhost, otherwise use production
$is_local = ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === 'localhost:8080' || $_SERVER['HTTP_HOST'] === '127.0.0.1:8080');
define('API_BASE_URL', $is_local ? 'http://localhost:8001' : 'https://shopnow-productos.onrender.com');
define('API_TIMEOUT', 10);

// Session Configuration
define('SESSION_NAME', 'admin_productos_session');
define('SESSION_TIMEOUT', 3600); // 1 hour
define('JWT_SECRET_KEY', 'your-secret-key-change-in-production'); // Change in production

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// Helper function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['jwt_token']) && isset($_SESSION['user']);
}

// Helper function to get JWT token
function getJWTToken() {
    return $_SESSION['jwt_token'] ?? null;
}

// Helper function to redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit();
    }
}

// Helper function to make API calls
function callAPI($endpoint, $method = 'GET', $data = null, $token = null, $queryParams = null) {
    $url = API_BASE_URL . $endpoint;
    
    // Add query parameters if provided
    if ($queryParams && is_array($queryParams)) {
        $queryString = http_build_query($queryParams);
        $url .= '?' . $queryString;
    }
    
    // Build curl command with proper escaping for both Linux and Windows
    $cmd = 'curl';
    $cmd .= ' -s'; // silent
    $cmd .= ' -X ' . escapeshellarg($method);
    $cmd .= ' -H ' . escapeshellarg('Content-Type: application/json');
    $cmd .= ' -H ' . escapeshellarg('Accept: application/json');
    $cmd .= ' --max-time ' . escapeshellarg((string)API_TIMEOUT);
    
    if ($token) {
        $cmd .= ' -H ' . escapeshellarg('Authorization: Bearer ' . $token);
    }
    
    if ($data !== null && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        $json_data = json_encode($data);
        $cmd .= ' -d ' . escapeshellarg($json_data);
    }
    
    $cmd .= ' ' . escapeshellarg($url);
    
    // Execute curl command - works on both Linux and Windows
    $response = shell_exec($cmd);
    
    if ($response === null) {
        // Log the failed command for debugging
        error_log("API call failed. Command: $cmd");
        error_log("API URL: " . API_BASE_URL . $endpoint);
        return [
            'success' => false,
            'error' => 'Unable to connect to the Productos service. Please verify it is running at ' . API_BASE_URL
        ];
    }
    
    // Trim response and decode JSON
    $response = trim($response);
    $decoded = json_decode($response, true);
    
    if ($decoded === null) {
        // JSON decode failed - log raw response for debugging
        error_log("JSON decode failed. Raw response: " . substr($response, 0, 500));
        return [
            'success' => false,
            'error' => 'Invalid response from Productos service'
        ];
    }
    
    return [
        'success' => true,
        'data' => $decoded
    ];
}

// Helper function to validate email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Helper function to validate price
function isValidPrice($price) {
    return is_numeric($price) && floatval($price) > 0;
}

// Helper function to validate quantity
function isValidQuantity($quantity) {
    return is_numeric($quantity) && intval($quantity) >= 0;
}

// Helper function to escape output
function esc($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

// Helper function to set flash message
function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

// Helper function to get and clear flash message
function getFlash() {
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}
