<?php
/**
 * Dashboard - List of Inventario Items
 */
require_once 'config.php';
requireLogin();

$items = [];
$error = '';
$success = '';

// Fetch inventario from API
$token = getJWTToken();
$result = callAPI('/inventario', 'GET', null, $token);

if ($result['success']) {
    $items = is_array($result['data']) ? $result['data'] : [];
} else {
    $error = $result['error'] ?? 'No se pudo obtener el inventario';
}

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = intval($_POST['id'] ?? 0);
    
    if ($id > 0) {
        $deleteResult = callAPI("/inventario/$id", 'DELETE', null, $token);
        
        if ($deleteResult['success']) {
            setFlash('success', 'Artículo de inventario eliminado exitosamente');
            header('Location: ' . BASE_PATH . 'dashboard.php');
            exit();
        } else {
            $error = $deleteResult['error'] ?? 'Error al eliminar el artículo';
        }
    }
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShopNow - Admin Inventario - Dashboard</title>
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
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h1 class="h3">
                    <i class="bi bi-box-seam-fill me-2 text-primary"></i>Gestión de Inventario
                </h1>
                <p class="text-muted">Administra el inventario de productos</p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="<?php echo BASE_PATH; ?>create.php" class="btn btn-primary btn-lg">
                    <i class="bi bi-plus-circle me-2"></i>Nuevo Artículo
                </a>
            </div>
        </div>
        
        <!-- Flash Messages -->
        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type'] === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                <i class="bi bi-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                <?php echo esc($flash['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i>
                <?php echo esc($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <!-- Inventario Table -->
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <?php if (empty($items)): ?>
                    <div class="p-4 text-center">
                        <i class="bi bi-inbox" style="font-size: 48px; color: #ccc;"></i>
                        <p class="text-muted mt-3">No hay artículos en el inventario</p>
                        <a href="<?php echo BASE_PATH; ?>create.php" class="btn btn-primary btn-sm mt-2">Agregar Primer Artículo</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="20%">ID Producto</th>
                                    <th width="30%">Cantidad en Stock</th>
                                    <th width="30%">Estado</th>
                                    <th width="20%">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-light text-dark" style="font-size: 1em;"><?php echo esc($item['id_producto']); ?></span>
                                        </td>
                                        <td>
                                            <strong><?php echo esc($item['cantidad']); ?> unidades</strong>
                                        </td>
                                        <td>
                                            <?php if ($item['cantidad'] > 10): ?>
                                                <span class="badge bg-success">Stock Disponible</span>
                                            <?php elseif ($item['cantidad'] > 0): ?>
                                                <span class="badge bg-warning">Stock Bajo</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Agotado</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="<?php echo BASE_PATH; ?>edit.php?id=<?php echo $item['id_producto']; ?>" class="btn btn-outline-primary" title="Editar">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $item['id_producto']; ?>" title="Eliminar">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                            
                                            <!-- Delete Confirmation Modal -->
                                            <div class="modal fade" id="deleteModal<?php echo $item['id_producto']; ?>" tabindex="-1">
                                                <div class="modal-dialog modal-sm">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Confirmar Eliminación</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>¿Estás seguro de que deseas eliminar este artículo del inventario?</p>
                                                            <strong>Producto ID: <?php echo esc($item['id_producto']); ?></strong>
                                                            <p class="text-muted">Cantidad: <?php echo esc($item['cantidad']); ?> unidades</p>
                                                            <div class="alert alert-info mt-3 mb-0">
                                                                <small><i class="bi bi-info-circle me-2"></i>Esta acción no se puede deshacer.</small>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="action" value="delete">
                                                                <input type="hidden" name="id" value="<?php echo $item['id_producto']; ?>">
                                                                <button type="submit" class="btn btn-danger">Eliminar</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer bg-light text-muted">
                        <small>Total de artículos: <strong><?php echo count($items); ?></strong></small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
