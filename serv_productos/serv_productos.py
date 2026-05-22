import psycopg2
from psycopg2.extras import RealDictCursor
from psycopg2 import pool
import os
from fastapi import FastAPI, HTTPException, Depends
from pydantic import BaseModel, Field
from typing import List, Optional
from auth import verify_token, create_access_token

# PostgreSQL connection configuration from environment variables
DB_CONFIG = {
    'host': os.getenv('DATABASE_HOST', 'localhost'),
    'user': os.getenv('DATABASE_USER', 'postgres'),
    'password': os.getenv('DATABASE_PASS', 'password'),
    'database': os.getenv('DATABASE_NAME', 'shopnow'),
    'port': int(os.getenv('DATABASE_PORT', '5432'))
}

# Create connection pool
connection_pool = None

def init_connection_pool():
    """Initialize the database connection pool"""
    global connection_pool
    try:
        print("Attempting to connect to PostgreSQL...")
        print(f"  Host: {DB_CONFIG['host']}")
        print(f"  Database: {DB_CONFIG['database']}")
        
        connection_pool = psycopg2.pool.SimpleConnectionPool(1, 20, **DB_CONFIG)
        print("✓ Connection pool created successfully")
        return True
    except psycopg2.OperationalError as e:
        print(f"✗ PostgreSQL connection error: {e}")
        connection_pool = None
        return False
    except Exception as e:
        print(f"✗ Unexpected error creating connection pool: {e}")
        connection_pool = None
        return False

app = FastAPI(
    title="Departamento de Productos",
    description="Servicio encargado de la custodia y registro oficial del catálogo de productos de la empresa.\n\n" \
    "Este servicio actúa como el punto central de integración para la validación de productos en los procesos de venta y gestión de pedidos. \n\n" \
    "Ejecutar en puerto **8001** y asegurarse de que los servicios de Pedidos (8002) y Clientes (8000) estén activos para su correcto funcionamiento. \n\n" \
    "**Versión PostgreSQL**: Almacenamiento en base de datos relacional con Render.com",
    version="3.0.0",
    contact={
        "name": "Arturo Barajas, Profesor de SOA - TecNM Querétaro",
    }
)

# Startup event to initialize database connection and schema
@app.on_event("startup")
async def startup_event():
    """Initialize database connection pool and create tables on startup"""
    if not init_connection_pool():
        print("⚠️  WARNING: Could not establish database connection on startup")
        return
    
    if connection_pool is None:
        print("⚠️  WARNING: Connection pool is None")
        return
    
    try:
        conn = connection_pool.getconn()
        cursor = conn.cursor()
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS productos (
                id_producto SERIAL PRIMARY KEY,
                descripcion VARCHAR(255) NOT NULL,
                precio DECIMAL(10, 2) NOT NULL CHECK (precio > 0),
                activo BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        """)
        conn.commit()
        print("✓ Database table 'productos' initialized successfully")
    except Exception as e:
        print(f"✗ Error initializing database table: {e}")
    finally:
        cursor.close()
        connection_pool.putconn(conn)

@app.on_event("shutdown")
async def shutdown_event():
    """Close database connection pool on shutdown"""
    global connection_pool
    if connection_pool:
        connection_pool.closeall()
        print("✓ Database connection pool closed")

class Producto(BaseModel):
    id_producto: int = Field(..., example=1) # type: ignore
    descripcion: str = Field(..., min_length=3, example="Laptop Gamer") # type: ignore
    precio: float = Field(..., gt=0, example=15000.0) # type: ignore
    activo: bool = Field(..., example=True) # type: ignore

class ProductoRegistro(BaseModel):
    descripcion: str = Field(..., min_length=3, example="Laptop Gamer") # type: ignore
    precio: float = Field(..., gt=0, example=15000.0) # type: ignore
    activo: bool = Field(default=True, example=True) # type: ignore

class ProductoUpdate(BaseModel):
    descripcion: Optional[str] = Field(None, min_length=3, example="Laptop Gamer") # type: ignore
    precio: Optional[float] = Field(None, gt=0, example=15000.0) # type: ignore
    activo: Optional[bool] = Field(None, example=True) # type: ignore

class LoginRequest(BaseModel):
    username: str = Field(..., example="admin") # type: ignore
    password: str = Field(..., example="password123") # type: ignore

def get_db_connection():
    """Get a connection from the pool"""
    if connection_pool is None:
        print("ERROR: Database connection pool is not available")
        raise HTTPException(
            status_code=503, 
            detail="Database connection unavailable. Check service logs for connection details."
        )
    try:
        return connection_pool.getconn()
    except Exception as e:
        print(f"ERROR getting connection from pool: {e}")
        raise HTTPException(status_code=503, detail="Failed to get database connection")

def return_db_connection(conn):
    """Return a connection to the pool"""
    if connection_pool is not None:
        connection_pool.putconn(conn)

def leer_productos():
    """Lee todos los productos de la base de datos PostgreSQL."""
    conn = get_db_connection()
    try:
        cursor = conn.cursor(cursor_factory=RealDictCursor)
        cursor.execute("SELECT id_producto, descripcion, precio, activo FROM productos ORDER BY id_producto")
        productos = cursor.fetchall()
        return [dict(p) for p in productos]
    except Exception as e:
        print(f"Error reading productos: {e}")
        raise HTTPException(status_code=500, detail="Error reading productos from database")
    finally:
        cursor.close()
        return_db_connection(conn)

@app.post(
    "/login",
    tags=["Autenticación"],
    summary="Obtener token JWT",
    status_code=200,
    responses={
        200: {
            "description": "Token obtenido exitosamente",
            "content": {
                "application/json": {
                    "example": {
                        "access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
                        "token_type": "bearer"
                    }
                }
            }
        },
        401: {
            "description": "Credenciales inválidas"
        }
    }
)
def login(credentials: LoginRequest):
    """Autentica un usuario y retorna un token JWT.
    
    Utiliza credenciales de demostración para esta versión.
    En producción, integrar con una base de datos de usuarios.
    
    Args:
        credentials: username y password
    
    Returns:
        dict: Token JWT para usar en headers de autenticación
    """
    # Credenciales de demostración (cambiar en producción)
    if credentials.username != "admin" or credentials.password != "password123":
        raise HTTPException(status_code=401, detail="Credenciales inválidas")
    
    token = create_access_token(data={"sub": credentials.username, "service": "productos"})
    return {"access_token": token, "token_type": "bearer"}

@app.get(
    "/productos",
    response_model=List[Producto],
    tags=["Consultas"],
    summary="Obtener lista de productos",
    status_code=200,
    responses={
        200: {
            "description": "Lista de productos obtenida exitosamente",
            "content": {
                "application/json": {
                    "example": [
                        {
                            "id_producto": 1,
                            "descripcion": "Laptop Gamer",
                            "precio": 15000.0,
                            "activo": True
                        }
                    ]
                }
            }
        }
    }
)
def obtener_productos(token: dict = Depends(verify_token)):
    """**Retorna el catálogo oficial de productos desde el archivo CSV.**
    
    Este endpoint obtiene la lista completa de todos los productos registrados
    en la base de datos de productos persistente (archivo CSV).
    
    **Returns**:

        List[Producto]:
            Lista de productos con todos sus datos (ID, descripción, precio).
    """
    return leer_productos()

@app.post(
    "/productos",
    tags=["Operaciones"],
    summary="Registrar nuevo producto",
    status_code=201,
    responses={
        201: {
            "description": "Producto registrado exitosamente",
            "content": {
                "application/json": {
                    "example": {
                        "mensaje": "Producto guardado exitosamente",
                        "id_producto": 1,
                        "status": "success"
                    }
                }
            }
        },
        422: {
            "description": "Datos de entrada inválidos o formato incorrecto"
        }
    }
)
def registrar_producto(nuevo: ProductoRegistro, token: dict = Depends(verify_token)):
    """**Registra un nuevo producto en el catálogo.**
    
    Crea un nuevo producto con el siguiente flujo:
        1. Valida los datos de entrada según el modelo ProductoRegistro
        2. Inserta el registro en la base de datos PostgreSQL
        3. Retorna el ID asignado
    
    **Args**:

        nuevo (ProductoRegistro): Datos del producto a registrar.
            - descripcion: Descripción del producto (mínimo 3 caracteres)
            - precio: Precio unitario del producto (debe ser mayor a 0)
            - activo (opcional): Estado del producto (por defecto: True)
    
    **Returns**:

        dict:
            Diccionario con mensaje de éxito e ID asignado del producto.
    """
    conn = get_db_connection()
    try:
        cursor = conn.cursor()
        cursor.execute("""
            INSERT INTO productos (descripcion, precio, activo)
            VALUES (%s, %s, %s)
            RETURNING id_producto
        """, (nuevo.descripcion, nuevo.precio, nuevo.activo))
        
        new_id = cursor.fetchone()[0]
        conn.commit()
        return {"mensaje": "Producto guardado exitosamente", "id_producto": new_id, "status": "success"}
    except Exception as e:
        conn.rollback()
        print(f"Error registering producto: {e}")
        raise HTTPException(status_code=500, detail="Error registering producto in database")
    finally:
        cursor.close()
        return_db_connection(conn)

@app.delete(
    "/productos/{id_producto}",
    tags=["Operaciones"],
    summary="Eliminar producto",
    status_code=200,
    responses={
        200: {
            "description": "Producto eliminado exitosamente",
            "content": {
                "application/json": {
                    "example": {
                        "mensaje": "Producto eliminado exitosamente",
                        "status": "success"
                    }
                }
            }
        },
        404: {
            "description": "Producto no encontrado con el ID especificado",
            "content": {
                "application/json": {
                    "example": {
                        "detail": "Producto no encontrado"
                    }
                }
            }
        }
    }
)
def eliminar_producto(id_producto: int, token: dict = Depends(verify_token)):
    """**Elimina un producto existente del catálogo.**
    
    Marca un producto como inactivo por su ID único. No se elimina
    físicamente el registro para evitar orfandad en inventario y pedidos.
    
    **Args**:

        id_producto (int): ID único del producto a eliminar.
    
    **Returns**:

        dict:
            Diccionario con mensaje de confirmación de eliminación.
    
    **Raises**:

        HTTPException:
            Con status 404 si el producto no existe.
    """
    conn = get_db_connection()
    try:
        cursor = conn.cursor()
        
        # Check if producto exists
        cursor.execute("SELECT id_producto FROM productos WHERE id_producto = %s", (id_producto,))
        if not cursor.fetchone():
            raise HTTPException(status_code=404, detail="Producto no encontrado")
        
        # Mark as inactive
        cursor.execute("""
            UPDATE productos
            SET activo = FALSE, updated_at = CURRENT_TIMESTAMP
            WHERE id_producto = %s
        """, (id_producto,))
        
        conn.commit()
        return {"mensaje": "Producto eliminado (inactivado) exitosamente", "status": "success"}
    except HTTPException:
        raise
    except Exception as e:
        conn.rollback()
        print(f"Error deleting producto: {e}")
        raise HTTPException(status_code=500, detail="Error deleting producto from database")
    finally:
        cursor.close()
        return_db_connection(conn)

@app.patch(
    "/productos/{id_producto}",
    tags=["Operaciones"],
    summary="Actualizar producto parcialmente",
    status_code=200,
    responses={
        200: {
            "description": "Producto actualizado parcialmente de forma exitosa",
            "content": {
                "application/json": {
                    "example": {
                        "mensaje": "Producto actualizado parcialmente exitosamente",
                        "status": "success"
                    }
                }
            }
        },
        404: {
            "description": "Producto no encontrado con el ID especificado",
            "content": {
                "application/json": {
                    "example": {
                        "detail": "Producto no encontrado"
                    }
                }
            }
        },
        422: {
            "description": "Datos de entrada inválidos o formato incorrecto"
        }
    }
)
def actualizar_producto_parcial(id_producto: int, update: ProductoUpdate, token: dict = Depends(verify_token)):
    """**Actualiza parcialmente un producto existente del catálogo.**
    
    Permite actualizar uno o más campos de un producto sin necesidad
    de proporcionar todos los datos. Los campos no proporcionados
    se mantienen sin cambios.
    
    **Args**:

        id_producto (int): ID único del producto a actualizar.
        update (ProductoUpdate): Datos opcionales a actualizar.
            - descripcion (opcional): Nueva descripción (mínimo 3 caracteres)
            - precio (opcional): Nuevo precio (debe ser mayor a 0)
            - activo (opcional): Nuevo estado de actividad
    
    **Returns**:

        dict:
            Diccionario con mensaje de confirmación de actualización.
    
    **Raises**:

        HTTPException:
            Con status 404 si el producto no existe.
    """
    conn = get_db_connection()
    try:
        cursor = conn.cursor()
        
        # Check if producto exists
        cursor.execute("SELECT id_producto FROM productos WHERE id_producto = %s", (id_producto,))
        if not cursor.fetchone():
            raise HTTPException(status_code=404, detail="Producto no encontrado")
        
        # Build dynamic update query
        updates = []
        params = []
        
        if update.descripcion is not None:
            updates.append("descripcion = %s")
            params.append(update.descripcion)
        if update.precio is not None:
            updates.append("precio = %s")
            params.append(update.precio)
        if update.activo is not None:
            updates.append("activo = %s")
            params.append(update.activo)
        
        # Add id_producto to params for WHERE clause
        params.append(id_producto)
        
        if updates:
            updates.append("updated_at = CURRENT_TIMESTAMP")
            query = f"UPDATE productos SET {', '.join(updates)} WHERE id_producto = %s"
            cursor.execute(query, params)
            conn.commit()
        
        return {"mensaje": "Producto actualizado parcialmente exitosamente", "status": "success"}
    except HTTPException:
        raise
    except Exception as e:
        conn.rollback()
        print(f"Error updating producto: {e}")
        raise HTTPException(status_code=500, detail="Error updating producto in database")
    finally:
        cursor.close()
        return_db_connection(conn)
