<?php
/**
 * Login Page - Admin Clientes Interface
 */
require_once 'config.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$loginAttempted = false;

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginAttempted = true;
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    // Validation
    if (empty($username) || empty($password)) {
        $error = 'Username and password are required';
    } else {
        // Call login endpoint
        $result = callAPI('/login', 'POST', [
            'username' => $username,
            'password' => $password
        ]);
        
        if ($result['success'] && isset($result['data']['access_token'])) {
            // Store JWT token and user info in session
            $_SESSION['jwt_token'] = $result['data']['access_token'];
            $_SESSION['user'] = $username;
            $_SESSION['login_time'] = time();
            
            // Redirect to dashboard
            header('Location: dashboard.php');
            exit();
        } else {
            $error = $result['data']['detail'] ?? 'Login failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShopNow - Admin Clientes - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            max-width: 400px;
            width: 100%;
            padding: 40px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            color: #667eea;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .login-header p {
            color: #6c757d;
            font-size: 14px;
            margin: 0;
        }
        
        .form-control {
            border-radius: 5px;
            border: 1px solid #e0e0e0;
            padding: 12px 15px;
            font-size: 14px;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 5px;
            padding: 12px;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        .alert {
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .demo-credentials {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            font-size: 13px;
        }
        
        .demo-credentials strong {
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>ShopNow</h1>
            <p>Admin - Gestión de Clientes</p>
        </div>
        
        <?php if ($error && $loginAttempted): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo esc($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username" class="form-label">Usuario</label>
                <input 
                    type="text" 
                    class="form-control" 
                    id="username" 
                    name="username" 
                    placeholder="Ingresa tu usuario"
                    required
                    autofocus
                    value="<?php echo $loginAttempted ? esc($_POST['username'] ?? '') : ''; ?>"
                >
            </div>
            
            <div class="form-group">
                <label for="password" class="form-label">Contraseña</label>
                <input 
                    type="password" 
                    class="form-control" 
                    id="password" 
                    name="password" 
                    placeholder="Ingresa tu contraseña"
                    required
                >
            </div>
            
            <button type="submit" class="btn btn-login btn-primary mb-3">
                Iniciar Sesión
            </button>
        </form>
        
        <div class="demo-credentials">
            <strong>Credenciales Demo:</strong><br>
            Usuario: <code>admin</code><br>
            Contraseña: <code>password123</code>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
