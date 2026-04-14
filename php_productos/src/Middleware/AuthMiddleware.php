<?php
namespace ShopNow\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthMiddleware
{
    private $secret;
    private $algorithm = 'HS256';
    
    public function __construct($secret)
    {
        $this->secret = $secret;
    }
    
    /**
     * Verify JWT token from Authorization header
     */
    public function verify()
    {
        $headers = getallheaders();
        
        if (!isset($headers['Authorization'])) {
            http_response_code(401);
            echo json_encode(['detail' => 'Token no encontrado']);
            exit();
        }
        
        // Extract bearer token
        $authHeader = $headers['Authorization'];
        if (!preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
            http_response_code(401);
            echo json_encode(['detail' => 'Formato de token inválido']);
            exit();
        }
        
        $token = $matches[1];
        
        try {
            $decoded = JWT::decode($token, new Key($this->secret, $this->algorithm));
            return $decoded;
        } catch (\Exception $e) {
            http_response_code(401);
            echo json_encode(['detail' => 'Token inválido o expirado: ' . $e->getMessage()]);
            exit();
        }
    }
    
    /**
     * Create JWT token
     */
    public function createToken($payload)
    {
        $payload['iat'] = time();
        $payload['exp'] = time() + (3600 * 24); // 24 hours
        
        return JWT::encode($payload, $this->secret, $this->algorithm);
    }
}
