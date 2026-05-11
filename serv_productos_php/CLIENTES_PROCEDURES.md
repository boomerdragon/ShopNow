# PostgreSQL Stored Procedures - Clientes Management

This document describes the PostgreSQL stored procedures for managing clients in the ShopNow system.

## Overview

Three stored procedures have been created to handle common client operations:
1. **sp_add_cliente** - Add a new client
2. **sp_modify_cliente** - Modify an existing client
3. **sp_deactivate_cliente** - Deactivate a client (soft delete)

---

## Stored Procedures

### 1. sp_add_cliente

Adds a new client to the system with validation for required fields and unique email constraint.

#### Function Signature
```sql
sp_add_cliente(
    p_nombre VARCHAR(255),
    p_correo VARCHAR(255),
    p_direccion VARCHAR(500) DEFAULT NULL,
    p_telefono VARCHAR(20) DEFAULT NULL
)
RETURNS TABLE(
    success BOOLEAN,
    id_cliente INTEGER,
    message VARCHAR(500)
)
```

#### Parameters
- **p_nombre** (required): Client name
- **p_correo** (required): Client email (must be unique)
- **p_direccion** (optional): Client address
- **p_telefono** (optional): Client phone number

#### Returns
- **success**: Boolean indicating if the operation was successful
- **id_cliente**: ID of newly created client (0 if failed)
- **message**: Status message describing the result

#### Validations
- Both `nombre` and `correo` are required
- `correo` must be unique in the database
- All string inputs are trimmed

#### Usage Example
```sql
-- Add a new client
SELECT * FROM sp_add_cliente(
    'Juan Pérez',
    'juan.perez@example.com',
    'Calle Principal 123',
    '+1-555-0123'
);

-- Expected output: (true, 1, "Cliente added successfully")
```

#### Error Cases
- Missing nombre or correo: Returns `(false, 0, "Error: [field] is required")`
- Duplicate email: Returns `(false, 0, "Error: correo already exists")`
- Database error: Returns `(false, 0, "Error: [error message]")`

---

### 2. sp_modify_cliente

Modifies an existing client. Only non-NULL parameters are updated, allowing partial updates.

#### Function Signature
```sql
sp_modify_cliente(
    p_id_cliente INTEGER,
    p_nombre VARCHAR(255) DEFAULT NULL,
    p_correo VARCHAR(255) DEFAULT NULL,
    p_direccion VARCHAR(500) DEFAULT NULL,
    p_telefono VARCHAR(20) DEFAULT NULL
)
RETURNS TABLE(
    success BOOLEAN,
    message VARCHAR(500)
)
```

#### Parameters
- **p_id_cliente** (required): ID of client to modify
- **p_nombre** (optional): New client name
- **p_correo** (optional): New email address
- **p_direccion** (optional): New address
- **p_telefono** (optional): New phone number

#### Returns
- **success**: Boolean indicating if the operation was successful
- **message**: Status message describing the result

#### Validations
- Client must exist in the database
- If `correo` is being updated, it must be unique
- String inputs are trimmed
- Empty strings ('') preserve existing values for optional fields
- `updated_at` timestamp is automatically set to current time

#### Usage Examples
```sql
-- Update client name and phone
SELECT * FROM sp_modify_cliente(
    1,
    'Juan Carlos Pérez',
    NULL,
    NULL,
    '+1-555-0456'
);

-- Update email only
SELECT * FROM sp_modify_cliente(
    1,
    p_correo := 'juancarlos.perez@newdomain.com'
);

-- Clear phone number (pass empty string)
SELECT * FROM sp_modify_cliente(
    1,
    p_telefono := ''
);
```

#### Error Cases
- Client not found: Returns `(false, "Error: cliente not found")`
- Duplicate email: Returns `(false, "Error: correo already exists")`
- Database error: Returns `(false, "Error: [error message]")`

---

### 3. sp_deactivate_cliente

Deactivates a client using soft delete (sets `activo` to false).

#### Function Signature
```sql
sp_deactivate_cliente(
    p_id_cliente INTEGER
)
RETURNS TABLE(
    success BOOLEAN,
    message VARCHAR(500)
)
```

#### Parameters
- **p_id_cliente** (required): ID of client to deactivate

#### Returns
- **success**: Boolean indicating if the operation was successful
- **message**: Status message describing the result

#### Behavior
- Client is not deleted from database but marked as inactive
- `updated_at` timestamp is set to current time
- All associated orders remain intact (due to RESTRICT foreign key constraint)
- Can be reactivated by updating the `activo` field to true

#### Usage Example
```sql
-- Deactivate client
SELECT * FROM sp_deactivate_cliente(1);

-- Expected output: (true, "Cliente deactivated successfully")
```

#### Error Cases
- Client not found: Returns `(false, "Error: cliente not found")`
- Already deactivated: Returns `(false, "Error: cliente is already deactivated")`
- Database error: Returns `(false, "Error: [error message]")`

---

## PHP Integration Example

### Using with Fat-Free Framework

```php
<?php
// In your ProductosController or ClientesController

public function addCliente($db) {
    $nombre = $this->request->POST['nombre'];
    $correo = $this->request->POST['correo'];
    $direccion = $this->request->POST['direccion'] ?? null;
    $telefono = $this->request->POST['telefono'] ?? null;
    
    $result = $db->exec(
        'SELECT * FROM sp_add_cliente(?, ?, ?, ?)',
        [$nombre, $correo, $direccion, $telefono]
    );
    
    if ($result[0]['success']) {
        return [
            'success' => true,
            'id_cliente' => $result[0]['id_cliente'],
            'message' => $result[0]['message']
        ];
    } else {
        return [
            'success' => false,
            'message' => $result[0]['message']
        ];
    }
}

public function updateCliente($db, $id_cliente) {
    $nombre = $this->request->POST['nombre'] ?? null;
    $correo = $this->request->POST['correo'] ?? null;
    $direccion = $this->request->POST['direccion'] ?? null;
    $telefono = $this->request->POST['telefono'] ?? null;
    
    $result = $db->exec(
        'SELECT * FROM sp_modify_cliente(?, ?, ?, ?, ?)',
        [$id_cliente, $nombre, $correo, $direccion, $telefono]
    );
    
    return [
        'success' => $result[0]['success'],
        'message' => $result[0]['message']
    ];
}

public function deactivateCliente($db, $id_cliente) {
    $result = $db->exec(
        'SELECT * FROM sp_deactivate_cliente(?)',
        [$id_cliente]
    );
    
    return [
        'success' => $result[0]['success'],
        'message' => $result[0]['message']
    ];
}
?>
```

---

## Direct SQL Execution

To use these procedures directly in your database client:

```sql
-- Add new client
SELECT * FROM sp_add_cliente('Maria García', 'maria.garcia@example.com', 'Avenida Central 456', '+1-555-0789');

-- Modify existing client
SELECT * FROM sp_modify_cliente(2, 'Maria Rosa García', NULL, 'Avenida Central 789', NULL);

-- Deactivate client
SELECT * FROM sp_deactivate_cliente(2);

-- Check all active clients
SELECT * FROM clientes WHERE activo = true;
```

---

## Best Practices

1. **Always check the `success` field** before proceeding with the result
2. **Use the `message` field** for user feedback and error reporting
3. **Trim inputs** - The procedures handle this automatically
4. **Handle duplicates gracefully** - The email unique constraint is validated
5. **Use soft deletes** - Clients are deactivated, not deleted, preserving data integrity
6. **Timestamps** - `created_at` and `updated_at` are managed automatically
7. **Transactions** - For complex operations involving multiple clients, use transactions to ensure data consistency

---

## Database Requirements

- PostgreSQL 9.6 or higher
- `plpgsql` procedural language enabled (typically enabled by default)

---

## Notes

- All procedures use `plpgsql` language
- They implement comprehensive error handling
- Email addresses are case-sensitive (standard PostgreSQL behavior)
- The `activo` field uses soft delete pattern for data preservation
- Foreign key relationships with `pedidos` table are maintained
