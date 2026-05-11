<?php
/**
 * Configuration file for Admin Clientes Interface
 * Decoupled UI for the Clientes microservice
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// API Configuration
define('API_BASE_URL', 'https://shopnow-clientes.onrender.com');
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
function callAPI($endpoint, $method = 'GET', $data = null, $token = null) {
    $url = API_BASE_URL . $endpoint;
    
    $options = [
        'http' => [
            'method' => $method,
            'header' => [
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            'timeout' => API_TIMEOUT
        ]
    ];
    
    // Add JWT token if provided
    if ($token) {
        $options['http']['header'][] = 'Authorization: Bearer ' . $token;
    }
    
    // Add data if provided
    if ($data !== null && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        $options['http']['content'] = json_encode($data);
    }
    
    $context = stream_context_create($options);
    
    try {
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            return [
                'success' => false,
                'error' => 'Unable to connect to the Clientes service. Please verify it is running on port 8000.'
            ];
        }
        
        $decoded = json_decode($response, true);
        return [
            'success' => true,
            'data' => $decoded
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
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
