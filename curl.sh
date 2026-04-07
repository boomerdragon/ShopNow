# Pedidos
### Listar
curl -X GET http://localhost:8002/pedidos

### Agregar
curl -X POST http://localhost:8002/pedidos \
    -H "Content-Type: application/json" \
    -d '{"id_cliente": 1, "id_producto": 1, "cantidad": 11}'

# Clientes
### Listar
curl -X GET http://localhost:8000/clientes

### Agregar
curl -X POST http://127.0.0.1:8000/clientes \
    -H "Content-Type: application/json" \
    -d '{"nombre": "Marco Iván", "correo": "marco@correo.com", "direccion": "MdeC #121", "telefono": "4429876543", "activo": true}'

### Eliminar (inactivar)
curl -X DELETE http://127.0.0.1:8000/clientes/1

### Modificar
curl -X PATCH http://127.0.0.1:8000/clientes/1 \
    -H "Content-Type: application/json" \
    -d '{"activo": "True"}'

# Productos
### Listar
curl -X GET http://localhost:8001/productos

### Agregar
curl -X POST http://localhost:8001/productos \
    -H "Content-Type: application/json" \
    -d '{"descripcion": "Producto X", "precio": "99.99", "activo": "True"}'

### Eliminar (inactivar)
curl -X DELETE http://localhost:8001/productos/20

### Modificar
curl -X PATCH http://localhost:8001/productos/20 \
    -H "Content-Type: application/json" \
    -d '{"activo": "True"}'

# Inventario
### Listar
curl -X GET http://localhost:8003/inventario

### Consultar un producto
curl -X GET http://localhost:8003/inventario/1

### Agregar producto al inventario con stock
curl -X POST http://localhost:8003/inventario \
    -H "Content-Type: application/json" \
    -d '{"id_producto": 20, "cantidad": 100}'

### Descontar inventario de producto
curl -X POST http://localhost:8003/inventario/descontar \
    -H "Content-Type: application/json" \
    -d '{"id_producto": 20, "cantidad": 5}'

### Agregar inventario a producto
curl -X POST http://localhost:8003/inventario/agregar \
    -H "Content-Type: application/json" \
    -d '{"id_producto": 20, "cantidad": 10}'