<?php
namespace ShopNow\Controllers;

use ShopNow\Middleware\AuthMiddleware;

class ProductosController
{
    private $f3;
    private $auth;
    private $dataFile;
    
    public function __construct($f3)
    {
        $this->f3 = $f3;
        $this->auth = new AuthMiddleware($f3->get('JWT_SECRET'));
        
        // Resolve absolute path for CSV file
        // Going from: /home/boomer/ITQ/SOA/ShopNow/php_productos/src/Controllers
        // To: /home/boomer/ITQ/SOA/ShopNow/productos.csv
        $projectRoot = dirname(__DIR__, 3); // Go up 3 levels to ShopNow
        $this->dataFile = $projectRoot . '/productos.csv';
        
        // Ensure CSV exists
        $this->initializeCSV();
    }
    
    /**
     * Initialize CSV file with headers if it doesn't exist
     */
    private function initializeCSV()
    {
        if (!file_exists($this->dataFile)) {
            $handle = fopen($this->dataFile, 'w');
            fputcsv($handle, ['id_producto', 'descripcion', 'precio', 'activo']);
            fclose($handle);
        }
    }
    
    /**
     * Read all productos from CSV file
     */
    private function leerProductos()
    {
        $productos = [];
        
        if (!file_exists($this->dataFile)) {
            return $productos;
        }
        
        $handle = fopen($this->dataFile, 'r');
        $headers = fgetcsv($handle);
        
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 4 || empty($row[0])) {
                continue; // Skip empty rows
            }
            
            try {
                $producto = [
                    'id_producto' => (int)$row[0],
                    'descripcion' => $row[1],
                    'precio' => (float)$row[2],
                    'activo' => strtolower($row[3]) === 'true' || $row[3] === '1'
                ];
                $productos[] = $producto;
            } catch (\Exception $e) {
                continue; // Skip invalid rows
            }
        }
        
        fclose($handle);
        return $productos;
    }
    
    /**
     * Write productos to CSV file
     */
    private function guardarProductos($productos)
    {
        $handle = fopen($this->dataFile, 'w');
        fputcsv($handle, ['id_producto', 'descripcion', 'precio', 'activo']);
        
        foreach ($productos as $p) {
            fputcsv($handle, [
                $p['id_producto'],
                $p['descripcion'],
                $p['precio'],
                $p['activo'] ? 'True' : 'False'
            ]);
        }
        
        fclose($handle);
    }
    
    /**
     * GET /productos
     * List all products
     */
    public function listar()
    {
        setJsonHeaders();
        
        // Verify token
        $this->auth->verify();
        
        $productos = $this->leerProductos();
        
        http_response_code(200);
        echo json_encode($productos);
    }
    
    /**
     * GET /productos/@id
     * Get single product by ID
     */
    public function obtener($f3, $params)
    {
        setJsonHeaders();
        
        // Verify token
        $this->auth->verify();
        
        $id = (int)$params['id'];
        $productos = $this->leerProductos();
        
        foreach ($productos as $p) {
            if ($p['id_producto'] === $id) {
                http_response_code(200);
                echo json_encode($p);
                return;
            }
        }
        
        http_response_code(404);
        echo json_encode([
            'detail' => 'Producto no encontrado'
        ]);
    }
    
    /**
     * POST /productos
     * Create new product
     */
    public function crear()
    {
        setJsonHeaders();
        
        // Verify token
        $this->auth->verify();
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate input
        if (!$input || !isset($input['descripcion']) || !isset($input['precio'])) {
            http_response_code(422);
            echo json_encode([
                'detail' => 'descripcion y precio son requeridos'
            ]);
            return;
        }
        
        $descripcion = trim($input['descripcion']);
        $precio = (float)$input['precio'];
        $activo = $input['activo'] ?? true;
        
        // Validate constraints
        if (strlen($descripcion) < 3) {
            http_response_code(422);
            echo json_encode([
                'detail' => 'descripcion debe tener al menos 3 caracteres'
            ]);
            return;
        }
        
        if ($precio <= 0) {
            http_response_code(422);
            echo json_encode([
                'detail' => 'precio debe ser mayor a 0'
            ]);
            return;
        }
        
        $productos = $this->leerProductos();
        
        // Generate next ID
        $siguiente_id = 1;
        if (!empty($productos)) {
            $siguiente_id = max(array_column($productos, 'id_producto')) + 1;
        }
        
        // Add new product
        $productos[] = [
            'id_producto' => $siguiente_id,
            'descripcion' => $descripcion,
            'precio' => $precio,
            'activo' => (bool)$activo
        ];
        
        $this->guardarProductos($productos);
        
        http_response_code(201);
        echo json_encode([
            'mensaje' => 'Producto guardado exitosamente',
            'id_producto' => $siguiente_id,
            'status' => 'success'
        ]);
    }
    
    /**
     * PUT /productos/@id
     * Update product
     */
    public function actualizar($f3, $params)
    {
        setJsonHeaders();
        
        // Verify token
        $this->auth->verify();
        
        $id = (int)$params['id'];
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            http_response_code(422);
            echo json_encode(['detail' => 'Invalid input']);
            return;
        }
        
        $productos = $this->leerProductos();
        $found = false;
        
        foreach ($productos as &$p) {
            if ($p['id_producto'] === $id) {
                $found = true;
                
                // Update allowed fields
                if (isset($input['descripcion'])) {
                    if (strlen($input['descripcion']) < 3) {
                        http_response_code(422);
                        echo json_encode(['detail' => 'descripcion must be at least 3 characters']);
                        return;
                    }
                    $p['descripcion'] = trim($input['descripcion']);
                }
                
                if (isset($input['precio'])) {
                    if ((float)$input['precio'] <= 0) {
                        http_response_code(422);
                        echo json_encode(['detail' => 'precio must be greater than 0']);
                        return;
                    }
                    $p['precio'] = (float)$input['precio'];
                }
                
                if (isset($input['activo'])) {
                    $p['activo'] = (bool)$input['activo'];
                }
                
                break;
            }
        }
        
        if (!$found) {
            http_response_code(404);
            echo json_encode(['detail' => 'Producto no encontrado']);
            return;
        }
        
        $this->guardarProductos($productos);
        
        http_response_code(200);
        echo json_encode([
            'mensaje' => 'Producto actualizado exitosamente',
            'status' => 'success'
        ]);
    }
    
    /**
     * DELETE /productos/@id
     * Delete (mark as inactive) product
     */
    public function eliminar($f3, $params)
    {
        setJsonHeaders();
        
        // Verify token
        $this->auth->verify();
        
        $id = (int)$params['id'];
        $productos = $this->leerProductos();
        $found = false;
        
        foreach ($productos as &$p) {
            if ($p['id_producto'] === $id) {
                $found = true;
                $p['activo'] = false; // Mark as inactive instead of deleting
                break;
            }
        }
        
        if (!$found) {
            http_response_code(404);
            echo json_encode(['detail' => 'Producto no encontrado']);
            return;
        }
        
        $this->guardarProductos($productos);
        
        http_response_code(200);
        echo json_encode([
            'mensaje' => 'Producto eliminado exitosamente',
            'status' => 'success'
        ]);
    }
}
