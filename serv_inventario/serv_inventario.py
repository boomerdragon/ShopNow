import psycopg2
from psycopg2.extras import RealDictCursor
from psycopg2 import pool
import os
import requests
import logging
from fastapi import FastAPI, HTTPException, Depends
from pydantic import BaseModel, Field
from typing import List
from auth import verify_token, create_access_token

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

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

# Productos service URL - supports both local and remote (Render)
PRODUCTOS_SERVICE_URL = os.getenv("PRODUCTOS_SERVICE_URL", "http://localhost:8001")
INVENTARIO_SERVICE_URL = os.getenv("INVENTARIO_SERVICE_URL", "http://localhost:8003")
PEDIDOS_SERVICE_URL = os.getenv("PEDIDOS_SERVICE_URL", "http://localhost:8002")
CLIENTES_SERVICE_URL = os.getenv("CLIENTES_SERVICE_URL", "http://localhost:8000")

app = FastAPI(
    title="Departamento de Inventario",
    description="Servicio encargado de la custodia y control de existencias físicas de productos.\n\n" \
    "Este servicio actúa como el punto central de integración para la validación de stock en los procesos de venta y gestión de pedidos. \n\n" \
    "Ejecutar en puerto **8003** y asegurarse de que los servicios de Pedidos (8002) y Productos (8001) estén activos para su correcto funcionamiento. \n\n" \
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
            CREATE TABLE IF NOT EXISTS inventario (
                id_producto INTEGER PRIMARY KEY,
                cantidad INTEGER NOT NULL CHECK (cantidad >= 0),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        """)
        conn.commit()
        print("✓ Database table 'inventario' initialized successfully")
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

# Contrato de Servicio (Formato Oficial)
class MovimientoInventario(BaseModel):
    id_producto: int = Field(..., example=1) # type: ignore
    cantidad: int = Field(..., gt=0, example=5) # type: ignore

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

def leer_inventario():
    """Lee todo el inventario de la base de datos PostgreSQL."""
    conn = get_db_connection()
    try:
        cursor = conn.cursor(cursor_factory=RealDictCursor)
        cursor.execute("SELECT id_producto, cantidad FROM inventario ORDER BY id_producto")
        items = cursor.fetchall()
        return [dict(item) for item in items]
    except Exception as e:
        print(f"Error reading inventario: {e}")
        raise HTTPException(status_code=500, detail="Error reading inventario from database")
    finally:
        cursor.close()
        return_db_connection(conn)

def verificar_producto_existe(id_producto: int) -> bool:
    """Verifica si un producto existe en el servicio de Productos.
    
    Realiza una solicitud HTTP al servicio de Productos para validar
    que el producto existe antes de permitir operaciones de inventario.
    
    Args:
        id_producto: ID del producto a verificar
        
    Returns:
        bool: True si el producto existe, False en caso contrario
        
    Raises:
        HTTPException: Si no se puede conectar con el servicio de Productos
    """
    try:
        response = requests.get(
            f"{PRODUCTOS_SERVICE_URL}/productos",
            timeout=10
        )
        
        if response.status_code == 200:
            productos = response.json()
            # Buscar el producto en la lista
            for producto in productos:
                if producto.get('id_producto') == id_producto:
                    logger.info(f"Producto {id_producto} encontrado en Productos service")
                    return True
            logger.warning(f"Producto {id_producto} NO encontrado en Productos service")
            return False
        else:
            logger.error(f"Error al consultar Productos service: {response.status_code}")
            raise HTTPException(
                status_code=503,
                detail="No se pudo verificar el producto. Servicio de Productos no disponible."
            )
    except requests.RequestException as e:
        logger.error(f"Error de conexión con Productos service: {str(e)}")
        raise HTTPException(
            status_code=503,
            detail=f"No se puede conectar con el servicio de Productos: {str(e)}"
        )
    except Exception as e:
        logger.error(f"Error inesperado al verificar producto: {str(e)}")
        raise HTTPException(
            status_code=500,
            detail=f"Error al verificar producto: {str(e)}"
        )

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
    
    token = create_access_token(data={"sub": credentials.username, "service": "inventario"})
    return {"access_token": token, "token_type": "bearer"}

@app.get(
    "/inventario",
    tags=["Consultas"],
    summary="Obtener inventario completo",
    status_code=200,
    responses={
        200: {
            "description": "Inventario completo obtenido exitosamente",
            "content": {
                "application/json": {
                    "example": [
                        {
                            "id_producto": 1,
                            "cantidad": 50
                        },
                        {
                            "id_producto": 2,
                            "cantidad": 30
                        }
                    ]
                }
            }
        }
    }
)
def obtener_inventario_completo(token: dict = Depends(verify_token)):
    """Retorna el inventario completo de todos los productos.
    
    Este endpoint obtiene la lista completa del stock disponible de todos
    los productos registrados en la base de datos de inventario persistente.
    
    Returns:
        list: Lista de movimientos de inventario con cantidad por producto.
    """
    return leer_inventario()

@app.get(
    "/inventario/{id_producto}",
    tags=["Consultas"],
    summary="Consultar stock de un producto",
    status_code=200,
    responses={
        200: {
            "description": "Stock del producto obtenido exitosamente",
            "content": {
                "application/json": {
                    "example": {
                        "id_producto": 1,
                        "cantidad": 50
                    }
                }
            }
        },
        404: {
            "description": "Producto no registrado en inventario",
            "content": {
                "application/json": {
                    "example": {
                        "detail": "Producto no registrado en inventario"
                    }
                }
            }
        }
    }
)
def consultar_stock(id_producto: int, token: dict = Depends(verify_token)):
    """Consulta la cantidad disponible de un producto específico.
    
    Busca y retorna el stock actual de un producto por su ID único.
    
    Args:
        id_producto (int): ID único del producto a consultar.
    
    Returns:
        dict: Diccionario con el ID del producto y cantidad disponible.
    
    Raises:
        HTTPException: Con status 404 si el producto no existe en inventario.
    """
    items = leer_inventario()
    for item in items:
        if item['id_producto'] == id_producto:
            return {"id_producto": id_producto, "cantidad": item['cantidad']}
    raise HTTPException(status_code=404, detail="Producto no registrado en inventario")

@app.post(
    "/inventario",
    tags=["Operaciones"],
    summary="Registrar nuevo producto en inventario",
    status_code=201,
    responses={
        201: {
            "description": "Producto registrado en inventario exitosamente",
            "content": {
                "application/json": {
                    "example": {
                        "mensaje": "Producto registrado en inventario",
                        "id_producto": 1,
                        "cantidad": 100,
                        "status": "success"
                    }
                }
            }
        },
        400: {
            "description": "El producto no existe en el catálogo o ya existe en inventario"
        },
        422: {
            "description": "Datos de entrada inválidos o formato incorrecto"
        },
        503: {
            "description": "Servicio de Productos no disponible"
        }
    }
)
def registrar_inventario(mov: MovimientoInventario, token: dict = Depends(verify_token)):
    """Registra un nuevo producto en el inventario.
    
    Crea un nuevo registro de inventario para un producto específico con
    la cantidad inicial de existencias. Primero valida que el producto
    exista en el catálogo de Productos.
    
    Args:
        mov (MovimientoInventario): Datos del movimiento de inventario.
            - id_producto: ID del producto a registrar
            - cantidad: Cantidad inicial de existencias (debe ser mayor a 0)
    
    Returns:
        dict: Diccionario con confirmación del registro y datos del producto.
    
    Raises:
        HTTPException: Con status 400 si el producto no existe en el catálogo.
        HTTPException: Con status 400 si el producto ya existe en inventario.
        HTTPException: Con status 503 si el servicio de Productos no está disponible.
    """
    # Verificar que el producto existe en el servicio de Productos
    if not verificar_producto_existe(mov.id_producto):
        raise HTTPException(
            status_code=400,
            detail=f"El producto {mov.id_producto} no existe en el catálogo de Productos. No se puede agregar al inventario."
        )
    
    # Verificar que el producto no exista ya en inventario
    conn = get_db_connection()
    try:
        cursor = conn.cursor(cursor_factory=RealDictCursor)
        cursor.execute("SELECT 1 FROM inventario WHERE id_producto = %s", (mov.id_producto,))
        if cursor.fetchone():
            raise HTTPException(status_code=400, detail="El producto ya existe en el inventario")

        cursor.execute(
            "INSERT INTO inventario (id_producto, cantidad) VALUES (%s, %s)",
            (mov.id_producto, mov.cantidad)
        )
        conn.commit()

        logger.info(f"Producto {mov.id_producto} registrado en inventario con cantidad {mov.cantidad}")
        return {"mensaje": "Producto registrado en inventario", "id_producto": mov.id_producto, "cantidad": mov.cantidad, "status": "success"}
    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"Error registrando inventario: {e}")
        raise HTTPException(status_code=500, detail="Error registrando inventario en la base de datos")
    finally:
        cursor.close()
        return_db_connection(conn)

@app.post(
    "/inventario/descontar",
    tags=["Operaciones"],
    summary="Descontar stock de inventario",
    status_code=200,
    responses={
        200: {
            "description": "Stock descontado exitosamente",
            "content": {
                "application/json": {
                    "example": {
                        "mensaje": "Descuento de inventario aplicado exitosamente",
                        "status": "success"
                    }
                }
            }
        },
        400: {
            "description": "Stock insuficiente en almacén o producto no existe en Productos"
        },
        404: {
            "description": "Producto no encontrado en inventario"
        },
        503: {
            "description": "Servicio de Productos no disponible"
        }
    }
)
def descontar_stock(mov: MovimientoInventario, token: dict = Depends(verify_token)):
    """Descuenta stock del inventario tras una venta exitosa.
    
    Reduce la cantidad disponible de un producto en el inventario.
    Se utiliza cuando se completa exitosamente un pedido.
    Valida que el producto exista en el catálogo de Productos.
    
    Args:
        mov (MovimientoInventario): Datos del movimiento de descuento.
            - id_producto: ID del producto a descontar
            - cantidad: Cantidad a descontar (debe ser mayor a 0)
    
    Returns:
        dict: Diccionario con confirmación de la operación.
    
    Raises:
        HTTPException: Con status 400 si stock es insuficiente o producto no existe en Productos.
        HTTPException: Con status 404 si el producto no existe en inventario.
        HTTPException: Con status 503 si el servicio de Productos no está disponible.
    """
    # Verificar que el producto existe en el servicio de Productos
    if not verificar_producto_existe(mov.id_producto):
        raise HTTPException(
            status_code=400,
            detail=f"El producto {mov.id_producto} no existe en el catálogo de Productos."
        )
    
    conn = get_db_connection()
    try:
        cursor = conn.cursor(cursor_factory=RealDictCursor)
        cursor.execute("SELECT cantidad FROM inventario WHERE id_producto = %s", (mov.id_producto,))
        producto = cursor.fetchone()
        if not producto:
            raise HTTPException(status_code=404, detail="Producto no encontrado en inventario")

        nueva_cantidad = producto['cantidad'] - mov.cantidad
        if nueva_cantidad < 0:
            raise HTTPException(status_code=400, detail="Stock insuficiente en almacén")

        cursor.execute(
            "UPDATE inventario SET cantidad = %s, updated_at = CURRENT_TIMESTAMP WHERE id_producto = %s",
            (nueva_cantidad, mov.id_producto)
        )
        conn.commit()

        logger.info(f"Stock descontado del producto {mov.id_producto}. Cantidad descontada: {mov.cantidad}")
        return {"mensaje": "Descuento de inventario aplicado exitosamente", "status": "success"}
    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"Error descontando stock en inventario: {e}")
        raise HTTPException(status_code=500, detail="Error actualizando inventario en la base de datos")
    finally:
        cursor.close()
        return_db_connection(conn)

@app.post(
    "/inventario/agregar",
    tags=["Operaciones"],
    summary="Agregar stock al inventario",
    status_code=200,
    responses={
        200: {
            "description": "Stock agregado exitosamente",
            "content": {
                "application/json": {
                    "example": {
                        "mensaje": "Existencias agregadas exitosamente",
                        "id_producto": 1,
                        "nueva_cantidad": 100,
                        "status": "success"
                    }
                }
            }
        },
        400: {
            "description": "El producto no existe en el catálogo de Productos"
        },
        404: {
            "description": "Producto no encontrado en inventario"
        },
        503: {
            "description": "Servicio de Productos no disponible"
        }
    }
)
def agregar_stock(mov: MovimientoInventario, token: dict = Depends(verify_token)):
    """Agrega stock al inventario de un producto.
    
    Incrementa la cantidad disponible de un producto en el inventario.
    Se utiliza para registrar compras de mercancía o devoluciones.
    Valida que el producto exista en el catálogo de Productos.
    
    Args:
        mov (MovimientoInventario): Datos del movimiento de adición.
            - id_producto: ID del producto a actualizar
            - cantidad: Cantidad a agregar (debe ser mayor a 0)
    
    Returns:
        dict: Diccionario con confirmación de la operación y nueva cantidad.
    
    Raises:
        HTTPException: Con status 400 si el producto no existe en Productos.
        HTTPException: Con status 404 si el producto no existe en inventario.
        HTTPException: Con status 503 si el servicio de Productos no está disponible.
    """
    # Verificar que el producto existe en el servicio de Productos
    if not verificar_producto_existe(mov.id_producto):
        raise HTTPException(
            status_code=400,
            detail=f"El producto {mov.id_producto} no existe en el catálogo de Productos."
        )
    
    conn = get_db_connection()
    try:
        cursor = conn.cursor(cursor_factory=RealDictCursor)
        cursor.execute("SELECT cantidad FROM inventario WHERE id_producto = %s", (mov.id_producto,))
        producto = cursor.fetchone()
        if not producto:
            raise HTTPException(status_code=404, detail="Producto no encontrado en inventario")

        nueva_cantidad = producto['cantidad'] + mov.cantidad
        cursor.execute(
            "UPDATE inventario SET cantidad = %s, updated_at = CURRENT_TIMESTAMP WHERE id_producto = %s",
            (nueva_cantidad, mov.id_producto)
        )
        conn.commit()

        logger.info(f"Stock agregado al producto {mov.id_producto}. Nueva cantidad: {nueva_cantidad}")
        return {"mensaje": "Existencias agregadas exitosamente", "id_producto": mov.id_producto, "nueva_cantidad": nueva_cantidad, "status": "success"}
    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"Error agregando stock en inventario: {e}")
        raise HTTPException(status_code=500, detail="Error actualizando inventario en la base de datos")
    finally:
        cursor.close()
        return_db_connection(conn)

