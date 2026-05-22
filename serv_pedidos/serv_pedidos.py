import psycopg2
from psycopg2.extras import RealDictCursor
from psycopg2 import pool
import os
import requests
from fastapi import FastAPI, HTTPException, Depends
from pydantic import BaseModel, Field
from typing import List
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

# Generar un token de servicio para llamadas internas entre servicios
SERVICE_TOKEN = create_access_token(data={"sub": "pedidos-service", "service": "pedidos"})

app = FastAPI(
    title="Coordinador de Pedidos",
    description="Servicio encargado de la coordinación y gestión de pedidos de venta, con validación de clientes e inventario. \n\n" \
    "Este servicio actúa como el punto central de integración entre los departamentos de Clientes, Productos e Inventario para garantizar la correcta ejecución de las ventas. \n\n" \
    "Ejecutar en puerto **8002** y asegurarse de que los servicios de Clientes (8000), Productos (8001) e Inventario (8003) estén activos para su correcto funcionamiento. \n\n" \
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
            CREATE TABLE IF NOT EXISTS pedidos (
                id_pedido SERIAL PRIMARY KEY,
                id_cliente INTEGER NOT NULL,
                id_producto INTEGER NOT NULL,
                cantidad INTEGER NOT NULL CHECK (cantidad > 0),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        """)
        conn.commit()
        print("✓ Database table 'pedidos' initialized successfully")
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

# URLs de los servicios
CLIENTES_URL = "http://localhost:8000"
PRODUCTOS_URL = "http://localhost:8001"
INVENTARIO_URL = "http://localhost:8003"

class Pedido(BaseModel):
    id_pedido: int = Field(..., example=501)  # type: ignore
    id_cliente: int = Field(..., example=101) # type: ignore
    id_producto: int = Field(..., example=1) # type: ignore
    cantidad: int = Field(..., gt=0, example=2) # type: ignore

class PedidoRegistro(BaseModel):
    id_cliente: int = Field(..., example=101) # type: ignore
    id_producto: int = Field(..., example=1) # type: ignore
    cantidad: int = Field(..., gt=0, example=2) # type: ignore

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

def leer_pedidos():
    """Lee todos los pedidos de la base de datos PostgreSQL."""
    conn = get_db_connection()
    try:
        cursor = conn.cursor(cursor_factory=RealDictCursor)
        cursor.execute("SELECT id_pedido, id_cliente, id_producto, cantidad FROM pedidos ORDER BY id_pedido")
        pedidos = cursor.fetchall()
        return [dict(p) for p in pedidos]
    except Exception as e:
        print(f"Error reading pedidos: {e}")
        raise HTTPException(status_code=500, detail="Error reading pedidos from database")
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
    
    token = create_access_token(data={"sub": credentials.username, "service": "pedidos"})
    return {"access_token": token, "token_type": "bearer"}

@app.get(
    "/pedidos",
    response_model=List[Pedido],
    tags=["Consultas"],
    summary="Obtener lista de pedidos",
    status_code=200,
    responses={
        200: {
            "description": "Lista de pedidos obtenida exitosamente",
            "content": {
                "application/json": {
                    "example": [
                        {
                            "id_pedido": 501,
                            "id_cliente": 101,
                            "id_producto": 1,
                            "cantidad": 2
                        }
                    ]
                }
            }
        }
    }
)
def obtener_pedidos(token: dict = Depends(verify_token)):
    """Retorna el registro oficial de pedidos desde el archivo CSV.
    
    Este endpoint obtiene la lista completa de todos los pedidos registrados
    en la base de datos de pedidos persistente (archivo CSV).
    
    Returns:
        List[Pedido]: Lista de pedidos con todos sus datos.
    """
    return leer_pedidos()

@app.post(
    "/pedidos",
    tags=["Operaciones"],
    summary="Crear nuevo pedido",
    status_code=201,
    responses={
        201: {
            "description": "Pedido creado exitosamente",
            "content": {
                "application/json": {
                    "example": {
                        "mensaje": "Venta completada y stock descontado",
                        "id_pedido": 501,
                        "status": "success"
                    }
                }
            }
        },
        400: {
            "description": "Datos inválidos: cliente no existe, producto no existe, inventario insuficiente"
        },
        503: {
            "description": "Servicios de catálogo o inventario no disponibles"
        }
    }
)
def crear_pedido(p: PedidoRegistro, token: dict = Depends(verify_token)):
    """Crea un nuevo pedido con validación integrada a través de HTTP.
    
    Ejecuta el siguiente flujo de validación usando llamadas HTTP:
    1. Consulta el servicio de productos para verificar que el producto existe
    2. Verifica que hay inventario suficiente para completar el pedido
    3. Valida que el cliente existe en el padrón oficial
    4. Si todo es válido: descuenta el inventario y persiste el pedido
    
    Args:
        p (PedidoRegistro): Datos del pedido a crear.
            - id_cliente: ID del cliente que realiza el pedido
            - id_producto: ID del producto a ordenar
            - cantidad: Cantidad de unidades a ordenar (debe ser mayor a 0)
    
    Returns:
        dict: Diccionario con mensaje de éxito e ID asignado del pedido.
    
    Raises:
        HTTPException: Con status 400 si hay problemas de validación.
        HTTPException: Con status 503 si no hay disponibilidad de servicios.
    """
    try:
        # PASO 1: Validar que el producto existe
        try:
            response = requests.get(f"{PRODUCTOS_URL}/productos", timeout=5, headers={"Authorization": f"Bearer {SERVICE_TOKEN}"})
            productos = response.json()
            existe_producto = any(prod['id_producto'] == p.id_producto for prod in productos)
            if not existe_producto:
                raise HTTPException(status_code=400, detail="Producto no existe en el catálogo")
        except Exception as e:
            print(f"Error consultando productos: {e}")
            raise HTTPException(status_code=503, detail="No se puede conectar al servicio de Productos")
        
        # PASO 2: Validar que hay inventario suficiente
        try:
            response = requests.get(f"{INVENTARIO_URL}/inventario/{p.id_producto}", timeout=5, headers={"Authorization": f"Bearer {SERVICE_TOKEN}"})
            if response.status_code == 404:
                raise HTTPException(status_code=400, detail="Producto sin registro de inventario")
            inventario = response.json()
            stock_actual = inventario.get('cantidad', 0)
            if p.cantidad > stock_actual:
                raise HTTPException(status_code=400, detail="Inventario insuficiente para completar el pedido")
        except HTTPException:
            raise
        except Exception as e:
            print(f"Error consultando inventario: {e}")
            raise HTTPException(status_code=503, detail="No se puede conectar al servicio de Inventario")
        
        # PASO 3: Validar que el cliente existe y está activo
        try:
            response = requests.get(f"{CLIENTES_URL}/clientes", timeout=5, headers={"Authorization": f"Bearer {SERVICE_TOKEN}"})
            clientes = response.json()
            cliente = next((cli for cli in clientes if cli['id_cliente'] == p.id_cliente), None)
            if not cliente:
                raise HTTPException(status_code=400, detail="El cliente no existe en el padrón oficial")
            if not cliente.get('activo', False):
                raise HTTPException(status_code=400, detail="No se puede crear pedidos para clientes inactivos")
        except HTTPException:
            raise
        except Exception as e:
            print(f"Error consultando clientes: {e}")
            raise HTTPException(status_code=503, detail="No se puede conectar al servicio de Clientes")
        
        # PASO 4: Descontar inventario
        try:
            response = requests.post(
                f"{INVENTARIO_URL}/inventario/descontar",
                json={"id_producto": p.id_producto, "cantidad": p.cantidad},
                timeout=5,
                headers={"Authorization": f"Bearer {SERVICE_TOKEN}"}
            )
            if response.status_code != 200:
                raise HTTPException(status_code=503, detail="Error al descontar inventario")
        except Exception as e:
            print(f"Error descuentan inventario: {e}")
            raise HTTPException(status_code=503, detail="No se puede descontar inventario")
        
        # PASO 5: Persistir pedido en PostgreSQL
        conn = get_db_connection()
        try:
            cursor = conn.cursor()
            cursor.execute("""
                INSERT INTO pedidos (id_cliente, id_producto, cantidad)
                VALUES (%s, %s, %s)
                RETURNING id_pedido
            """, (p.id_cliente, p.id_producto, p.cantidad))
            
            new_id = cursor.fetchone()[0]
            conn.commit()
            return {"mensaje": "Venta completada y stock descontado", "id_pedido": new_id, "status": "success"}
        except Exception as e:
            conn.rollback()
            print(f"Error inserting pedido: {e}")
            raise HTTPException(status_code=500, detail="Error registering pedido in database")
        finally:
            cursor.close()
            return_db_connection(conn)

    except HTTPException:
        raise
    except Exception as e:
        print(f"Error en crear_pedido: {e}")
        raise HTTPException(status_code=503, detail="Error de comunicación con servicios")
