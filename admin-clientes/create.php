<?php
/**
 * Create Cliente Form
 */
require_once 'config.php';
requireLogin();

$errors = [];
$success = false;
$formData = [
    'nombre' => '',
    'correo' => '',
    'direccion' => '',
    'telefono' => '',
    'activo' => true
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'nombre' => trim($_POST['nombre'] ?? ''),
        'correo' => trim($_POST['correo'] ?? ''),
        'direccion' => trim($_POST['direccion'] ?? ''),
        'telefono' => trim($_POST['telefono'] ?? ''),
        'activo' => isset($_POST['activo']) && $_POST['activo'] === '1'
    ];
    
    // Validation
    if (empty($formData['nombre'])) {
        $errors['nombre'] = 'El nombre es requerido';
    } elseif (strlen($formData['nombre']) < 3) {
        $errors['nombre'] = 'El nombre debe tener al menos 3 caracteres';
    }
    
    if (empty($formData['correo'])) {
        $errors['correo'] = 'El correo es requerido';
    } elseif (!isValidEmail($formData['correo'])) {
        $errors['correo'] = 'El correo no es válido';
    }
    
    if (empty($formData['direccion'])) {
        $errors['direccion'] = 'La dirección es requerida';
    }
    
    if (empty($formData['telefono'])) {
        $errors['telefono'] = 'El teléfono es requerido';
    } elseif (!isValidPhone($formData['telefono'])) {
        $errors['telefono'] = 'El teléfono debe tener 10 dígitos';
    }
    
    // If no validation errors, submit to API
    if (empty($errors)) {
        $token = getJWTToken();
        $result = callAPI('/clientes', 'POST', $formData, $token);
        
        if ($result['success']) {
            setFlash('success', 'Cliente creado exitosamente');
            header('Location: ' . BASE_PATH . 'dashboard.php');
            exit();
        } else {
            $errors['general'] = $result['error'] ?? 'Error al crear el cliente';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShopNow - Admin Clientes - Crear Cliente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo BASE_PATH; ?>css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="<?php echo BASE_PATH; ?>dashboard.php">
                <i class="bi bi-shop me-2"></i>ShopNow Admin
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <span class="navbar-text me-3">
                            <i class="bi bi-person-circle me-2"></i>
                            <?php echo esc($_SESSION['user']); ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_PATH; ?>logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <div class="container py-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col">
                <div class="d-flex align-items-center gap-3">
                    <a href="<?php echo BASE_PATH; ?>dashboard.php" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-chevron-left me-1"></i>Volver
                    </a>
                    <div>
                        <h1 class="h3 mb-0">
                            <i class="bi bi-person-plus-fill me-2 text-primary"></i>Crear Nuevo Cliente
                        </h1>
                        <p class="text-muted mb-0">Completa el formulario para registrar un nuevo cliente</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Form Card -->
        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-circle me-2"></i>
                                <strong>Por favor, corrige los siguientes errores:</strong>
                                <ul class="mb-0 mt-2">
                                    <?php foreach ($errors as $field => $message): ?>
                                        <?php if ($field !== 'general'): ?>
                                            <li><?php echo esc($message); ?></li>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="" novalidate>
                            <!-- Nombre -->
                            <div class="mb-3">
                                <label for="nombre" class="form-label">
                                    <i class="bi bi-person me-1"></i>Nombre Completo
                                    <span class="text-danger">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    class="form-control <?php echo isset($errors['nombre']) ? 'is-invalid' : ''; ?>"
                                    id="nombre" 
                                    name="nombre" 
                                    placeholder="Juan Pérez García"
                                    value="<?php echo esc($formData['nombre']); ?>"
                                    required
                                    minlength="3"
                                >
                                <?php if (isset($errors['nombre'])): ?>
                                    <div class="invalid-feedback d-block">
                                        <?php echo esc($errors['nombre']); ?>
                                    </div>
                                <?php endif; ?>
                                <small class="text-muted">Mínimo 3 caracteres</small>
                            </div>
                            
                            <!-- Correo -->
                            <div class="mb-3">
                                <label for="correo" class="form-label">
                                    <i class="bi bi-envelope me-1"></i>Correo Electrónico
                                    <span class="text-danger">*</span>
                                </label>
                                <input 
                                    type="email" 
                                    class="form-control <?php echo isset($errors['correo']) ? 'is-invalid' : ''; ?>"
                                    id="correo" 
                                    name="correo" 
                                    placeholder="juan@ejemplo.com"
                                    value="<?php echo esc($formData['correo']); ?>"
                                    required
                                >
                                <?php if (isset($errors['correo'])): ?>
                                    <div class="invalid-feedback d-block">
                                        <?php echo esc($errors['correo']); ?>
                                    </div>
                                <?php endif; ?>
                                <small class="text-muted">Debe ser un correo válido y único</small>
                            </div>
                            
                            <!-- Dirección -->
                            <div class="mb-3">
                                <label for="direccion" class="form-label">
                                    <i class="bi bi-geo-alt me-1"></i>Dirección
                                    <span class="text-danger">*</span>
                                </label>
                                <textarea 
                                    class="form-control <?php echo isset($errors['direccion']) ? 'is-invalid' : ''; ?>"
                                    id="direccion" 
                                    name="direccion" 
                                    placeholder="Calle Principal 123, Apartamento 4B"
                                    rows="3"
                                    required
                                ><?php echo esc($formData['direccion']); ?></textarea>
                                <?php if (isset($errors['direccion'])): ?>
                                    <div class="invalid-feedback d-block">
                                        <?php echo esc($errors['direccion']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Teléfono -->
                            <div class="mb-3">
                                <label for="telefono" class="form-label">
                                    <i class="bi bi-telephone me-1"></i>Teléfono
                                    <span class="text-danger">*</span>
                                </label>
                                <input 
                                    type="tel" 
                                    class="form-control <?php echo isset($errors['telefono']) ? 'is-invalid' : ''; ?>"
                                    id="telefono" 
                                    name="telefono" 
                                    placeholder="4421234567"
                                    value="<?php echo esc($formData['telefono']); ?>"
                                    pattern="\d{10}"
                                    required
                                >
                                <?php if (isset($errors['telefono'])): ?>
                                    <div class="invalid-feedback d-block">
                                        <?php echo esc($errors['telefono']); ?>
                                    </div>
                                <?php endif; ?>
                                <small class="text-muted">Debe tener exactamente 10 dígitos</small>
                            </div>
                            
                            <!-- Activo -->
                            <div class="mb-4">
                                <div class="form-check form-switch">
                                    <input 
                                        class="form-check-input" 
                                        type="checkbox" 
                                        id="activo" 
                                        name="activo" 
                                        value="1"
                                        <?php echo $formData['activo'] ? 'checked' : ''; ?>
                                    >
                                    <label class="form-check-label" for="activo">
                                        Cliente activo
                                    </label>
                                </div>
                                <small class="text-muted d-block mt-2">El cliente podrá realizar pedidos si está activo</small>
                            </div>
                            
                            <!-- Buttons -->
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-lg flex-grow-1">
                                    <i class="bi bi-check-circle me-2"></i>Crear Cliente
                                </button>
                                <a href="dashboard.php" class="btn btn-outline-secondary btn-lg">
                                    <i class="bi bi-x-circle me-2"></i>Cancelar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Info Sidebar -->
            <div class="col-lg-4">
                <div class="card shadow-sm bg-light">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-info-circle me-2 text-info"></i>Información Importante
                        </h5>
                        <div class="list-group list-group-flush">
                            <div class="list-group-item bg-transparent">
                                <small><strong>Nombre:</strong> Debe tener al menos 3 caracteres</small>
                            </div>
                            <div class="list-group-item bg-transparent">
                                <small><strong>Correo:</strong> Debe ser único en el sistema</small>
                            </div>
                            <div class="list-group-item bg-transparent">
                                <small><strong>Teléfono:</strong> Exactamente 10 dígitos sin separadores</small>
                            </div>
                            <div class="list-group-item bg-transparent">
                                <small><strong>Activo:</strong> Define si el cliente puede hacer compras</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
