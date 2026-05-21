<?php
/**
 * Edit Inventario Item Form
 */
require_once 'config.php';
requireLogin();

$id = intval($_GET['id'] ?? 0);
$errors = [];
$formData = null;
$notFound = false;

// Fetch inventario item data
$token = getJWTToken();
// Since the API doesn't support GET /inventario/{id}, fetch all and find by ID
$result = callAPI("/inventario", 'GET', null, $token);

if ($result['success'] && is_array($result['data'])) {
    // Find the item with matching ID
    foreach ($result['data'] as $item) {
        if (isset($item['id_producto']) && $item['id_producto'] == $id) {
            $formData = $item;
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
        'id_producto' => intval($_POST['id_producto'] ?? 0),
        'cantidad' => intval($_POST['cantidad'] ?? 0)
    ];
    
    // Validation
    if ($updateData['id_producto'] <= 0) {
        $errors['id_producto'] = 'El ID del producto debe ser un número positivo';
    }
    
    if ($updateData['cantidad'] <= 0) {
        $errors['cantidad'] = 'La cantidad debe ser mayor a 0';
    } elseif ($updateData['cantidad'] > 999999) {
        $errors['cantidad'] = 'La cantidad no puede exceder 999999';
    }
    
    // If no validation errors, submit to API
    if (empty($errors)) {
        $updateResult = callAPI("/inventario/$id", 'PATCH', $updateData, $token);
        
        if ($updateResult['success']) {
            setFlash('success', 'Artículo de inventario actualizado exitosamente');
            header('Location: ' . BASE_PATH . 'dashboard.php');
            exit();
        } else {
            $errors['general'] = $updateResult['error'] ?? 'Error al actualizar el artículo';
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
    <title>ShopNow - Admin Inventario - Editar Artículo</title>
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
                            <i class="bi bi-pencil-square me-2 text-primary"></i>Editar Artículo de Inventario
                        </h1>
                        <p class="text-muted mb-0">Actualiza la cantidad del producto en stock</p>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if ($notFound): ?>
            <!-- Not Found Alert -->
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i>
                <strong>Artículo no encontrado</strong><br>
                El artículo de inventario que intentas editar no existe o ha sido eliminado.
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
                                <i class="bi bi-box me-2"></i>Producto ID: <span class="badge bg-primary"><?php echo $formData['id_producto']; ?></span>
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
                                        value="<?php echo esc((string)($formData['id_producto'] ?? '')); ?>"
                                        min="1"
                                        required
                                        readonly
                                    >
                                    <?php if (isset($errors['id_producto'])): ?>
                                        <div class="invalid-feedback d-block">
                                            <?php echo esc($errors['id_producto']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <small class="text-muted">Este campo no puede ser modificado</small>
                                </div>
                                
                                <!-- Cantidad -->
                                <div class="mb-4">
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
                                        value="<?php echo esc((string)($formData['cantidad'] ?? '')); ?>"
                                        min="1"
                                        max="999999"
                                        required
                                    >
                                    <?php if (isset($errors['cantidad'])): ?>
                                        <div class="invalid-feedback d-block">
                                            <?php echo esc($errors['cantidad']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <small class="text-muted">Número de unidades disponibles en stock</small>
                                </div>
                                
                                <!-- Buttons -->
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg flex-grow-1">
                                        <i class="bi bi-check-circle me-2"></i>Actualizar Artículo
                                    </button>
                                    <a href="<?php echo BASE_PATH; ?>dashboard.php" class="btn btn-outline-secondary btn-lg">
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
                                <i class="bi bi-info-circle me-2 text-info"></i>Nivel de Stock
                            </h5>
                            <div class="list-group list-group-flush">
                                <div class="list-group-item bg-transparent">
                                    <small><strong>Stock Actual:</strong><br><?php echo esc((string)($formData['cantidad'] ?? 0)); ?> unidades</small>
                                </div>
                                <?php if ($formData['cantidad'] > 10): ?>
                                    <div class="list-group-item bg-transparent">
                                        <small><span class="badge bg-success">Stock Disponible</span></small>
                                    </div>
                                <?php elseif ($formData['cantidad'] > 0): ?>
                                    <div class="list-group-item bg-transparent">
                                        <small><span class="badge bg-warning">Stock Bajo</span></small>
                                    </div>
                                <?php else: ?>
                                    <div class="list-group-item bg-transparent">
                                        <small><span class="badge bg-danger">Agotado</span></small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card shadow-sm bg-light mt-3">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="bi bi-info-circle me-2 text-info"></i>Consejos
                            </h5>
                            <div class="list-group list-group-flush">
                                <div class="list-group-item bg-transparent">
                                    <small><strong>ID Producto:</strong> No puede cambiar</small>
                                </div>
                                <div class="list-group-item bg-transparent">
                                    <small><strong>Cantidad:</strong> 1 - 999999 unidades</small>
                                </div>
                                <div class="list-group-item bg-transparent">
                                    <small><strong>Alerta:</strong> Stock menor a 10</small>
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
