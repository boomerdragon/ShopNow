<?php
/**
 * Edit Producto Form
 */
require_once 'config.php';
requireLogin();

$id = intval($_GET['id'] ?? 0);
$errors = [];
$formData = null;
$notFound = false;

// Fetch producto data
$token = getJWTToken();
// Since the API doesn't support GET /productos/{id}, fetch all and find by ID
$result = callAPI("/productos", 'GET', null, $token, ['include_inactive' => 'true']);

if ($result['success'] && is_array($result['data'])) {
    // Find the producto with matching ID
    foreach ($result['data'] as $producto) {
        if (isset($producto['id_producto']) && $producto['id_producto'] == $id) {
            $formData = $producto;
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
        'descripcion' => trim($_POST['descripcion'] ?? ''),
        'precio' => trim($_POST['precio'] ?? ''),
        'cantidad' => trim($_POST['cantidad'] ?? ''),
        'activo' => isset($_POST['activo']) && $_POST['activo'] === '1'
    ];
    
    // Validation
    if (empty($updateData['nombre'])) {
        $errors['nombre'] = 'El nombre es requerido';
    } elseif (strlen($updateData['nombre']) < 3) {
        $errors['nombre'] = 'El nombre debe tener al menos 3 caracteres';
    }
    
    if (empty($updateData['descripcion'])) {
        $errors['descripcion'] = 'La descripción es requerida';
    }
    
    if (empty($updateData['precio'])) {
        $errors['precio'] = 'El precio es requerido';
    } elseif (!isValidPrice($updateData['precio'])) {
        $errors['precio'] = 'El precio debe ser un número mayor a 0';
    }
    
    if (empty($updateData['cantidad'])) {
        $errors['cantidad'] = 'La cantidad es requerida';
    } elseif (!isValidQuantity($updateData['cantidad'])) {
        $errors['cantidad'] = 'La cantidad debe ser un número mayor o igual a 0';
    }
    
    // If no validation errors, submit to API
    if (empty($errors)) {
        $updateData['precio'] = floatval($updateData['precio']);
        $updateData['cantidad'] = intval($updateData['cantidad']);
        
        $updateResult = callAPI("/productos/$id", 'PATCH', $updateData, $token);
        
        if ($updateResult['success']) {
            setFlash('success', 'Producto actualizado exitosamente');
            header('Location: ' . BASE_PATH . 'dashboard.php');
            exit();
        } else {
            $errors['general'] = $updateResult['error'] ?? 'Error al actualizar el producto';
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
    <title>ShopNow - Admin Productos - Editar Producto</title>
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
                            <i class="bi bi-pencil-square me-2 text-primary"></i>Editar Producto
                        </h1>
                        <p class="text-muted mb-0">Actualiza la información del producto</p>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if ($notFound): ?>
            <!-- Not Found Alert -->
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i>
                <strong>Producto no encontrado</strong><br>
                El producto que intentas editar no existe o ha sido eliminado.
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
                                <i class="bi bi-box-seam me-2"></i>ID: <span class="badge bg-primary"><?php echo $formData['id_producto']; ?></span>
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
                                        <i class="bi bi-box-seam me-1"></i>Nombre del Producto
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input 
                                        type="text" 
                                        class="form-control <?php echo isset($errors['nombre']) ? 'is-invalid' : ''; ?>"
                                        id="nombre" 
                                        name="nombre" 
                                        placeholder="Ej: Laptop Dell XPS 13"
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
                                
                                <!-- Descripción -->
                                <div class="mb-3">
                                    <label for="descripcion" class="form-label">
                                        <i class="bi bi-textarea me-1"></i>Descripción
                                        <span class="text-danger">*</span>
                                    </label>
                                    <textarea 
                                        class="form-control <?php echo isset($errors['descripcion']) ? 'is-invalid' : ''; ?>"
                                        id="descripcion" 
                                        name="descripcion" 
                                        placeholder="Describe las características principales del producto"
                                        rows="4"
                                        required
                                    ><?php echo esc($formData['descripcion'] ?? ''); ?></textarea>
                                    <?php if (isset($errors['descripcion'])): ?>
                                        <div class="invalid-feedback d-block">
                                            <?php echo esc($errors['descripcion']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Precio -->
                                <div class="mb-3">
                                    <label for="precio" class="form-label">
                                        <i class="bi bi-currency-dollar me-1"></i>Precio (USD)
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input 
                                        type="number" 
                                        class="form-control <?php echo isset($errors['precio']) ? 'is-invalid' : ''; ?>"
                                        id="precio" 
                                        name="precio" 
                                        placeholder="0.00"
                                        value="<?php echo esc($formData['precio'] ?? ''); ?>"
                                        required
                                        step="0.01"
                                        min="0.01"
                                    >
                                    <?php if (isset($errors['precio'])): ?>
                                        <div class="invalid-feedback d-block">
                                            <?php echo esc($errors['precio']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Cantidad -->
                                <div class="mb-3">
                                    <label for="cantidad" class="form-label">
                                        <i class="bi bi-boxes me-1"></i>Cantidad en Stock
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input 
                                        type="number" 
                                        class="form-control <?php echo isset($errors['cantidad']) ? 'is-invalid' : ''; ?>"
                                        id="cantidad" 
                                        name="cantidad" 
                                        placeholder="0"
                                        value="<?php echo esc($formData['cantidad'] ?? ''); ?>"
                                        required
                                        min="0"
                                        step="1"
                                    >
                                    <?php if (isset($errors['cantidad'])): ?>
                                        <div class="invalid-feedback d-block">
                                            <?php echo esc($errors['cantidad']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Estado -->
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input 
                                            class="form-check-input" 
                                            type="checkbox" 
                                            id="activo" 
                                            name="activo" 
                                            value="1"
                                            <?php echo ($formData['activo'] ?? true) ? 'checked' : ''; ?>
                                        >
                                        <label class="form-check-label" for="activo">
                                            <i class="bi bi-check-circle me-1"></i>Producto Activo
                                        </label>
                                    </div>
                                </div>
                                
                                <!-- Buttons -->
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check-circle me-2"></i>Guardar Cambios
                                    </button>
                                    <a href="<?php echo BASE_PATH; ?>dashboard.php" class="btn btn-secondary">
                                        <i class="bi bi-x-circle me-2"></i>Cancelar
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo BASE_PATH; ?>js/script.js"></script>
</body>
</html>
