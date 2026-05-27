<?php
/**
 * Configuration file for Admin Clientes Interface
 * Decoupled UI for the Clientes microservice
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Determine the base path for the application
// This allows the app to work from any subdirectory like /admin-clientes/
$scriptPath = dirname($_SERVER['SCRIPT_NAME']);
$basePath = ($scriptPath === '/' || $scriptPath === '\\') ? '/' : rtrim($scriptPath, '/') . '/';
define('BASE_PATH', $basePath);

// API Configuration - Auto-detect environment
// Use local development URL if running on localhost, otherwise use production
$is_local = ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === 'localhost:8080' || $_SERVER['HTTP_HOST'] === '127.0.0.1:8080');

// Allow overriding the API base URL via environment variable (useful in deployments)
$env_api_base = getenv('API_BASE_URL');
if ($env_api_base && is_string($env_api_base) && trim($env_api_base) !== '') {
    // Ensure no trailing slash
    define('API_BASE_URL', rtrim(trim($env_api_base), '/'));
} else {
    define('API_BASE_URL', $is_local ? 'http://localhost:8000' : 'https://shopnow-clientes.onrender.com');
}
define('API_TIMEOUT', 10);

// Session Configuration
define('SESSION_NAME', 'admin_clientes_session');
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

    // Use PHP cURL for more robust HTTP handling and status codes
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, (int)API_TIMEOUT);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    $headers = [
        'Content-Type: application/json',
        'Accept: application/json'
    ];

    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    if ($data !== null && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        $json_data = json_encode($data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
    }

    $response = curl_exec($ch);
    $curlErr = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $response === null) {
        error_log("cURL error calling API: $curlErr");
        error_log("API URL: $url");
        return [
            'success' => false,
            'error' => 'Unable to connect to the Clientes service. Please verify it is reachable at ' . API_BASE_URL
        ];
    }

    $response = trim($response);
    $decoded = json_decode($response, true);

    if ($decoded === null) {
        error_log("JSON decode failed. Raw response: " . substr($response, 0, 500));
        return [
            'success' => false,
            'error' => 'Invalid response from Clientes service'
        ];
    }

    // Treat non-2xx responses as errors and surface the API message
    if ($httpCode < 200 || $httpCode >= 300) {
        $apiError = $decoded['detail'] ?? $decoded['error'] ?? ($decoded['message'] ?? 'Unknown error');
        return [
            'success' => false,
            'error' => "Clientes service returned HTTP $httpCode: " . $apiError
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

// Helper function to validate phone
function isValidPhone($phone) {
    return preg_match('/^\d{10}$/', $phone) === 1;
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
