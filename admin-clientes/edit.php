<?php
/**
 * Edit Cliente Form
 */
require_once 'config.php';
requireLogin();

$id = intval($_GET['id'] ?? 0);
$errors = [];
$formData = null;
$notFound = false;

// Fetch cliente data
$token = getJWTToken();
// Since the API doesn't support GET /clientes/{id}, fetch all and find by ID
$result = callAPI("/clientes", 'GET', null, $token, ['include_inactive' => 'true']);

if ($result['success'] && is_array($result['data'])) {
    // Find the cliente with matching ID
    foreach ($result['data'] as $cliente) {
        if (isset($cliente['id_cliente']) && $cliente['id_cliente'] == $id) {
            $formData = $cliente;
            break;
        }
    }
    if ($formData === null) {
        $notFound = true;
    }
} else {
    $notFound = true;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$notFound) {
    $updateData = [
        'nombre' => trim($_POST['nombre'] ?? ''),
        'correo' => trim($_POST['correo'] ?? ''),
        'direccion' => trim($_POST['direccion'] ?? ''),
        'telefono' => trim($_POST['telefono'] ?? ''),
        'activo' => isset($_POST['activo']) && $_POST['activo'] === '1'
    ];
    
    // Validation
    if (empty($updateData['nombre'])) {
        $errors['nombre'] = 'El nombre es requerido';
    } elseif (strlen($updateData['nombre']) < 3) {
        $errors['nombre'] = 'El nombre debe tener al menos 3 caracteres';
    }
    
    if (empty($updateData['correo'])) {
        $errors['correo'] = 'El correo es requerido';
    } elseif (!isValidEmail($updateData['correo'])) {
        $errors['correo'] = 'El correo no es válido';
    }
    
    if (empty($updateData['direccion'])) {
        $errors['direccion'] = 'La dirección es requerida';
    }
    
    if (empty($updateData['telefono'])) {
        $errors['telefono'] = 'El teléfono es requerido';
    } elseif (!isValidPhone($updateData['telefono'])) {
        $errors['telefono'] = 'El teléfono debe tener 10 dígitos';
    }
    
    // If no validation errors, submit to API
    if (empty($errors)) {
        $updateResult = callAPI("/clientes/$id", 'PATCH', $updateData, $token);
        
        if ($updateResult['success']) {
            setFlash('success', 'Cliente actualizado exitosamente');
            header('Location: ' . BASE_PATH . 'dashboard.php');
            exit();
        } else {
            $errors['general'] = $updateResult['error'] ?? 'Error al actualizar el cliente';
        }
    }
    
    // Keep form data for display
    $formData = $updateData;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShopNow - Admin Clientes - Editar Cliente</title>
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
                            <i class="bi bi-pencil-square me-2 text-primary"></i>Editar Cliente
                        </h1>
                        <p class="text-muted mb-0">Actualiza la información del cliente</p>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if ($notFound): ?>
            <!-- Not Found Alert -->
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i>
                <strong>Cliente no encontrado</strong><br>
                El cliente que intentas editar no existe o ha sido eliminado.
                <a href="<?php echo BASE_PATH; ?>dashboard.php" class="alert-link">Volver al listado</a>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php else: ?>
            <!-- Form Card -->
            <div class="row">
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">
                                <i class="bi bi-person-badge me-2"></i>ID: <span class="badge bg-primary"><?php echo $formData['id_cliente']; ?></span>
                            </h5>
                        </div>
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
                                        value="<?php echo esc($formData['nombre'] ?? ''); ?>"
                                        required
                                        minlength="3"
                                    >
                                    <?php if (isset($errors['nombre'])): ?>
                                        <div class="invalid-feedback d-block">
                                            <?php echo esc($errors['nombre']); ?>
                                        </div>
                                    <?php endif; ?>
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
                                        value="<?php echo esc($formData['correo'] ?? ''); ?>"
                                        required
                                    >
                                    <?php if (isset($errors['correo'])): ?>
                                        <div class="invalid-feedback d-block">
                                            <?php echo esc($errors['correo']); ?>
                                        </div>
                                    <?php endif; ?>
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
                                    ><?php echo esc($formData['direccion'] ?? ''); ?></textarea>
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
                                        value="<?php echo esc($formData['telefono'] ?? ''); ?>"
                                        pattern="\d{10}"
                                        required
                                    >
                                    <?php if (isset($errors['telefono'])): ?>
                                        <div class="invalid-feedback d-block">
                                            <?php echo esc($errors['telefono']); ?>
                                        </div>
                                    <?php endif; ?>
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
                                            <?php echo ($formData['activo'] ?? false) ? 'checked' : ''; ?>
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
                                        <i class="bi bi-check-circle me-2"></i>Actualizar Cliente
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
                                <i class="bi bi-clock-history me-2 text-info"></i>Historial
                            </h5>
                            <div class="list-group list-group-flush">
                                <div class="list-group-item bg-transparent">
                                    <small><strong>Creado:</strong><br><?php echo esc($formData['created_at'] ?? 'N/A'); ?></small>
                                </div>
                                <div class="list-group-item bg-transparent">
                                    <small><strong>Actualizado:</strong><br><?php echo esc($formData['updated_at'] ?? 'N/A'); ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card shadow-sm bg-light mt-3">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="bi bi-info-circle me-2 text-info"></i>Información Importante
                            </h5>
                            <div class="list-group list-group-flush">
                                <div class="list-group-item bg-transparent">
                                    <small><strong>Nombre:</strong> Mín. 3 caracteres</small>
                                </div>
                                <div class="list-group-item bg-transparent">
                                    <small><strong>Correo:</strong> Debe ser válido</small>
                                </div>
                                <div class="list-group-item bg-transparent">
                                    <small><strong>Teléfono:</strong> 10 dígitos</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
