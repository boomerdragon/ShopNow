<?php
namespace ShopNow\Controllers;

use ShopNow\Middleware\AuthMiddleware;

class AuthController
{
    private $f3;
    private $auth;
    
    public function __construct($f3)
    {
        $this->f3 = $f3;
        $this->auth = new AuthMiddleware($f3->get('JWT_SECRET'));
    }
    
    /**
     * POST /login
     * Authenticate user and return JWT token
     */
    public function login()
    {
        setJsonHeaders();
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['username']) || !isset($input['password'])) {
            http_response_code(422);
            echo json_encode([
                'detail' => 'username y password son requeridos'
            ]);
            return;
        }
        
        // Demo credentials (change in production)
        if ($input['username'] !== 'admin' || $input['password'] !== 'password123') {
            http_response_code(401);
            echo json_encode([
                'detail' => 'Credenciales inválidas'
            ]);
            return;
        }
        
        $token = $this->auth->createToken([
            'sub' => $input['username'],
            'service' => 'productos'
        ]);
        
        http_response_code(200);
        echo json_encode([
            'access_token' => $token,
            'token_type' => 'bearer'
        ]);
    }
}
