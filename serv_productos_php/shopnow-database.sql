-- ============================================
-- CLIENTES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS clientes (
    id_cliente SERIAL PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    correo VARCHAR(255) UNIQUE NOT NULL,
    direccion VARCHAR(500),
    telefono VARCHAR(20),
    activo BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- PRODUCTOS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS productos (
    id_producto SERIAL PRIMARY KEY,
    descripcion VARCHAR(255) NOT NULL,
    precio DECIMAL(10, 2) NOT NULL,
    activo BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- PEDIDOS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS pedidos (
    id_pedido SERIAL PRIMARY KEY,
    id_cliente INTEGER NOT NULL,
    id_producto INTEGER NOT NULL,
    cantidad INTEGER NOT NULL CHECK (cantidad > 0),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign Key Constraints
    CONSTRAINT fk_pedidos_cliente 
        FOREIGN KEY (id_cliente) 
        REFERENCES clientes(id_cliente) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE,
    
    CONSTRAINT fk_pedidos_producto 
        FOREIGN KEY (id_producto) 
        REFERENCES productos(id_producto) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE
);

-- ============================================
-- INDEXES (for Performance)
-- ============================================
CREATE INDEX IF NOT EXISTS idx_pedidos_cliente ON pedidos(id_cliente);
CREATE INDEX IF NOT EXISTS idx_pedidos_producto ON pedidos(id_producto);

-- ============================================
-- STORED PROCEDURES - CLIENTES MANAGEMENT
-- ============================================

-- ============================================
-- SP_ADD_CLIENTE
-- Purpose: Add a new client to the system
-- Parameters:
--   p_nombre: Client name (required)
--   p_correo: Client email (required, must be unique)
--   p_direccion: Client address (optional)
--   p_telefono: Client phone (optional)
-- Returns: id_cliente (newly created client ID) or error message
-- ============================================
CREATE OR REPLACE FUNCTION sp_add_cliente(
    p_nombre VARCHAR(255),
    p_correo VARCHAR(255),
    p_direccion VARCHAR(500) DEFAULT NULL,
    p_telefono VARCHAR(20) DEFAULT NULL
)
RETURNS TABLE(
    success BOOLEAN,
    id_cliente INTEGER,
    message VARCHAR(500)
) AS $$
DECLARE
    v_id_cliente INTEGER;
BEGIN
    -- Validate required fields
    IF p_nombre IS NULL OR p_nombre = '' THEN
        RETURN QUERY SELECT false, 0::INTEGER, 'Error: nombre is required'::VARCHAR(500);
        RETURN;
    END IF;
    
    IF p_correo IS NULL OR p_correo = '' THEN
        RETURN QUERY SELECT false, 0::INTEGER, 'Error: correo is required'::VARCHAR(500);
        RETURN;
    END IF;
    
    -- Check if email already exists
    IF EXISTS(SELECT 1 FROM clientes WHERE correo = p_correo) THEN
        RETURN QUERY SELECT false, 0::INTEGER, 'Error: correo already exists'::VARCHAR(500);
        RETURN;
    END IF;
    
    -- Insert new client
    INSERT INTO clientes (nombre, correo, direccion, telefono, activo, created_at, updated_at)
    VALUES (
        TRIM(p_nombre),
        TRIM(p_correo),
        CASE WHEN p_direccion IS NOT NULL THEN TRIM(p_direccion) ELSE NULL END,
        CASE WHEN p_telefono IS NOT NULL THEN TRIM(p_telefono) ELSE NULL END,
        true,
        CURRENT_TIMESTAMP,
        CURRENT_TIMESTAMP
    )
    RETURNING clientes.id_cliente INTO v_id_cliente;
    
    -- Return success
    RETURN QUERY SELECT true, v_id_cliente, 'Cliente added successfully'::VARCHAR(500);
    
EXCEPTION WHEN OTHERS THEN
    RETURN QUERY SELECT false, 0::INTEGER, ('Error: ' || SQLERRM)::VARCHAR(500);
END;
$$ LANGUAGE plpgsql;

-- ============================================
-- SP_MODIFY_CLIENTE
-- Purpose: Modify an existing client
-- Parameters:
--   p_id_cliente: Client ID (required)
--   p_nombre: Client name (optional, if NULL maintains current value)
--   p_correo: Client email (optional, if NULL maintains current value)
--   p_direccion: Client address (optional, if NULL maintains current value)
--   p_telefono: Client phone (optional, if NULL maintains current value)
-- Returns: success status and message
-- ============================================
CREATE OR REPLACE FUNCTION sp_modify_cliente(
    p_id_cliente INTEGER,
    p_nombre VARCHAR(255) DEFAULT NULL,
    p_correo VARCHAR(255) DEFAULT NULL,
    p_direccion VARCHAR(500) DEFAULT NULL,
    p_telefono VARCHAR(20) DEFAULT NULL
)
RETURNS TABLE(
    success BOOLEAN,
    message VARCHAR(500)
) AS $$
DECLARE
    v_existing_correo VARCHAR(255);
BEGIN
    -- Validate client exists
    IF NOT EXISTS(SELECT 1 FROM clientes WHERE id_cliente = p_id_cliente) THEN
        RETURN QUERY SELECT false, 'Error: cliente not found'::VARCHAR(500);
        RETURN;
    END IF;
    
    -- If correo is being updated, check for duplicate
    IF p_correo IS NOT NULL AND p_correo != '' THEN
        SELECT correo INTO v_existing_correo FROM clientes WHERE id_cliente = p_id_cliente;
        
        IF p_correo != v_existing_correo AND EXISTS(SELECT 1 FROM clientes WHERE correo = p_correo) THEN
            RETURN QUERY SELECT false, 'Error: correo already exists'::VARCHAR(500);
            RETURN;
        END IF;
    END IF;
    
    -- Update client with non-NULL values
    UPDATE clientes
    SET
        nombre = COALESCE(NULLIF(TRIM(p_nombre), ''), nombre),
        correo = COALESCE(NULLIF(TRIM(p_correo), ''), correo),
        direccion = CASE 
            WHEN p_direccion IS NOT NULL THEN TRIM(p_direccion)
            WHEN p_direccion = '' THEN NULL
            ELSE direccion
        END,
        telefono = CASE 
            WHEN p_telefono IS NOT NULL THEN TRIM(p_telefono)
            WHEN p_telefono = '' THEN NULL
            ELSE telefono
        END,
        updated_at = CURRENT_TIMESTAMP
    WHERE id_cliente = p_id_cliente;
    
    RETURN QUERY SELECT true, 'Cliente modified successfully'::VARCHAR(500);
    
EXCEPTION WHEN OTHERS THEN
    RETURN QUERY SELECT false, ('Error: ' || SQLERRM)::VARCHAR(500);
END;
$$ LANGUAGE plpgsql;

-- ============================================
-- SP_DEACTIVATE_CLIENTE
-- Purpose: Deactivate a client (soft delete)
-- Parameters:
--   p_id_cliente: Client ID to deactivate
-- Returns: success status and message
-- ============================================
CREATE OR REPLACE FUNCTION sp_deactivate_cliente(
    p_id_cliente INTEGER
)
RETURNS TABLE(
    success BOOLEAN,
    message VARCHAR(500)
) AS $$
BEGIN
    -- Validate client exists
    IF NOT EXISTS(SELECT 1 FROM clientes WHERE id_cliente = p_id_cliente) THEN
        RETURN QUERY SELECT false, 'Error: cliente not found'::VARCHAR(500);
        RETURN;
    END IF;
    
    -- Check if already deactivated
    IF EXISTS(SELECT 1 FROM clientes WHERE id_cliente = p_id_cliente AND activo = false) THEN
        RETURN QUERY SELECT false, 'Error: cliente is already deactivated'::VARCHAR(500);
        RETURN;
    END IF;
    
    -- Deactivate client
    UPDATE clientes
    SET
        activo = false,
        updated_at = CURRENT_TIMESTAMP
    WHERE id_cliente = p_id_cliente;
    
    RETURN QUERY SELECT true, 'Cliente deactivated successfully'::VARCHAR(500);
    
EXCEPTION WHEN OTHERS THEN
    RETURN QUERY SELECT false, ('Error: ' || SQLERRM)::VARCHAR(500);
END;
$$ LANGUAGE plpgsql;