<?php
/**
 * Create Inventario Item Form
 */
require_once 'config.php';
requireLogin();

$errors = [];
$success = false;
$formData = [
    'id_producto' => '',
    'cantidad' => ''
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'id_producto' => intval($_POST['id_producto'] ?? 0),
        'cantidad' => intval($_POST['cantidad'] ?? 0)
    ];
    
    // Validation
    if ($formData['id_producto'] <= 0) {
        $errors['id_producto'] = 'El ID del producto debe ser un número positivo';
    }
    
    if ($formData['cantidad'] <= 0) {
        $errors['cantidad'] = 'La cantidad debe ser mayor a 0';
    } elseif ($formData['cantidad'] > 999999) {
        $errors['cantidad'] = 'La cantidad no puede exceder 999999';
    }
    
    // If no validation errors, submit to API
    if (empty($errors)) {
        $token = getJWTToken();
        $result = callAPI('/inventario', 'POST', $formData, $token);
        
        if ($result['success']) {
            setFlash('success', 'Artículo de inventario creado exitosamente');
            header('Location: ' . BASE_PATH . 'dashboard.php');
            exit();
        } else {
            $errors['general'] = $result['error'] ?? 'Error al crear el artículo de inventario';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShopNow - Admin Inventario - Crear Artículo</title>
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
                            <i class="bi bi-plus-circle-fill me-2 text-primary"></i>Agregar Nuevo Artículo de Inventario
                        </h1>
                        <p class="text-muted mb-0">Completa el formulario para registrar un nuevo artículo</p>
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
                            <!-- ID Producto -->
                            <div class="mb-3">
                                <label for="id_producto" class="form-label">
                                    <i class="bi bi-hash me-1"></i>ID del Producto
                                    <span class="text-danger">*</span>
                                </label>
                                <input 
                                    type="number" 
                                    class="form-control <?php echo isset($errors['id_producto']) ? 'is-invalid' : ''; ?>" 
                                    id="id_producto" 
                                    name="id_producto" 
                                    placeholder="Ejemplo: 1" 
                                    min="1"
                                    value="<?php echo esc((string)$formData['id_producto']); ?>" 
                                    required
                                >
                                <?php if (isset($errors['id_producto'])): ?>
                                    <div class="invalid-feedback">
                                        <?php echo esc($errors['id_producto']); ?>
                                    </div>
                                <?php endif; ?>
                                <small class="text-muted">El identificador único del producto</small>
                            </div>
                            
                            <!-- Cantidad -->
                            <div class="mb-3">
                                <label for="cantidad" class="form-label">
                                    <i class="bi bi-box me-1"></i>Cantidad en Stock
                                    <span class="text-danger">*</span>
                                </label>
                                <input 
                                    type="number" 
                                    class="form-control <?php echo isset($errors['cantidad']) ? 'is-invalid' : ''; ?>" 
                                    id="cantidad" 
                                    name="cantidad" 
                                    placeholder="Ejemplo: 50" 
                                    min="1"
                                    max="999999"
                                    value="<?php echo esc((string)$formData['cantidad']); ?>" 
                                    required
                                >
                                <?php if (isset($errors['cantidad'])): ?>
                                    <div class="invalid-feedback">
                                        <?php echo esc($errors['cantidad']); ?>
                                    </div>
                                <?php endif; ?>
                                <small class="text-muted">Número de unidades disponibles en stock</small>
                            </div>
                            
                            <!-- Buttons -->
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-check-circle me-2"></i>Crear Artículo
                                </button>
                                <a href="<?php echo BASE_PATH; ?>dashboard.php" class="btn btn-outline-secondary btn-lg">
                                    <i class="bi bi-x-circle me-2"></i>Cancelar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Info Card -->
            <div class="col-lg-4">
                <div class="card shadow-sm bg-light">
                    <div class="card-body">
                        <h6 class="card-title mb-3">
                            <i class="bi bi-info-circle me-2 text-info"></i>Consejos
                        </h6>
                        <ul class="small">
                            <li>Asegúrate de que el ID del producto sea válido</li>
                            <li>Ingresa la cantidad total en stock disponible</li>
                            <li>Puedes actualizar la cantidad más tarde</li>
                            <li>Los artículos con stock menor a 10 se mostrarán en rojo</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
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
