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
function callAPI($endpoint, $method = 'GET', $data = null, $token = null, $queryParams = null) {
    $url = API_BASE_URL . $endpoint;
    
    // Add query parameters if provided
    if ($queryParams && is_array($queryParams)) {
        $queryString = http_build_query($queryParams);
        $url .= '?' . $queryString;
    }
    
    // Use curl command as fallback when PHP curl extension is not available
    $cmd = "curl -s -X $method -H \"Content-Type: application/json\" -H \"Accept: application/json\"";
    
    if ($token) {
        $cmd .= " -H \"Authorization: Bearer $token\"";
    }
    
    if ($data !== null && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        $json_data = json_encode($data);
        // Escape quotes for cmd.exe
        $escaped_json = str_replace('"', '\\"', $json_data);
        $cmd .= " -d \"$escaped_json\"";
    }
    
    $cmd .= " \"$url\"";
    
    // Use cmd /c to ensure it runs in cmd.exe
    $full_cmd = "cmd /c $cmd";
    $response = shell_exec($full_cmd);
    
    if ($response === null) {
        return [
            'success' => false,
            'error' => 'Unable to connect to the Clientes service. Please verify it is running.'
        ];
    }
    
    $decoded = json_decode($response, true);
    if ($decoded === null) {
        // If JSON decode fails, check if it's an error response
        if (strpos($response, 'detail') !== false) {
            $decoded = json_decode($response, true);
        }
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
