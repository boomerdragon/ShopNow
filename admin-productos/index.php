<?php
/**
 * Login Page - Admin Productos Interface
 */
require_once 'config.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    header('Location: ' . BASE_PATH . 'dashboard.php');
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
        
        // Check if the API call was successful
        if (!$result['success']) {
            $error = $result['error'] ?? 'Login failed. Please try again.';
        } elseif (isset($result['data']['access_token'])) {
            // Store JWT token and user info in session
            $_SESSION['jwt_token'] = $result['data']['access_token'];
            $_SESSION['user'] = $username;
            $_SESSION['login_time'] = time();
            
            // Redirect to dashboard
            header('Location: ' . BASE_PATH . 'dashboard.php');
            exit();
        } else {
            // API responded but no access_token in response
            $error = $result['data']['detail'] ?? $result['data']['message'] ?? 'Login failed. Invalid credentials.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShopNow - Admin Productos - Login</title>
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
        
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px;
            font-weight: 600;
            border-radius: 5px;
            width: 100%;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }
        
        .alert {
            border-radius: 8px;
            border: none;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>🛍️ ShopNow</h1>
            <p>Admin Productos</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Error:</strong> <?php echo esc($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="mb-3">
                <label for="username" class="form-label">Usuario</label>
                <input 
                    type="text" 
                    class="form-control" 
                    id="username" 
                    name="username" 
                    placeholder="admin"
                    value="<?php echo $loginAttempted ? esc($_POST['username'] ?? '') : ''; ?>"
                    required
                    autofocus
                >
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">Contraseña</label>
                <input 
                    type="password" 
                    class="form-control" 
                    id="password" 
                    name="password" 
                    placeholder="••••••••"
                    required
                >
            </div>
            
            <button type="submit" class="btn btn-primary btn-login text-white">
                Iniciar Sesión
            </button>
        </form>
        
        <div class="text-center mt-4">
            <p class="text-muted" style="font-size: 12px;">
                Asegúrate de que el servicio Productos esté ejecutándose en puerto 8001
            </p>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
