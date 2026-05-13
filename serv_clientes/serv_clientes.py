import psycopg2
from psycopg2.extras import RealDictCursor
from psycopg2 import pool
from fastapi import FastAPI, HTTPException, Depends, Query
from pydantic import BaseModel, Field, EmailStr
from typing import List, Optional
from auth import verify_token, create_access_token

# PostgreSQL connection configuration
DB_CONFIG = {
    'host': 'dpg-d7ohmhpj2pic73abp6l0-a.oregon-postgres.render.com',
    'user': 'shopnow_663n_user',
    'password': 'mJKZ4Bs3pW5XqeK5c5FLlukVy1TUGEIl',
    'database': 'shopnow_663n',
    'port': 5432
}

# Create connection pool
connection_pool = None

def init_connection_pool():
    """Initialize the database connection pool"""
    global connection_pool
    try:
        print("Attempting to connect to PostgreSQL...")
        print(f"  Host: {DB_CONFIG['host']}")
        print(f"  User: {DB_CONFIG['user']}")
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
    title="Departamento de Clientes",
    description="Servicio encargado de la custodia y registro oficial de los clientes de la empresa. \n\n" \
    "Este servicio actúa como el punto central de integración para la validación de clientes en los procesos de venta y atención al cliente. \n\n" \
    "Ejecutar en puerto **8000** y asegurarse de que los servicios de Pedidos (8002) y Productos (8001) estén activos para su correcto funcionamiento. \n\n" \
    "**Versión PostgreSQL**: Almacenamiento en base de datos relacional con Render.com",
    version="4.0.0",
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
            CREATE TABLE IF NOT EXISTS clientes (
                id_cliente SERIAL PRIMARY KEY,
                nombre VARCHAR(255) NOT NULL,
                correo VARCHAR(255) NOT NULL UNIQUE,
                direccion VARCHAR(255) NOT NULL,
                telefono VARCHAR(20) NOT NULL,
                activo BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        """)
        conn.commit()
        print("✓ Database table 'clientes' initialized successfully")
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

class Cliente(BaseModel):
    id_cliente: int = Field(..., example=101, description="ID numérico único") # type: ignore
    nombre: str = Field(..., min_length=3, example="Juan Pérez") # type: ignore
    correo: str = Field(..., example="juan@ejemplo.com") # type: ignore
    direccion: str = Field(..., example="Calle 123") # type: ignore
    telefono: str = Field(..., example="4421234567") # type: ignore
    activo: bool = Field(..., example=True) # type: ignore

class ClienteRegistro(BaseModel):
    nombre: str = Field(..., min_length=3, example="Juan Pérez") # type: ignore
    correo: EmailStr = Field(..., example="juan@ejemplo.com") # type: ignore
    direccion: str = Field(..., example="Calle 123") # type: ignore
    telefono: str = Field(..., min_length=10, max_length=10, example="4421234567") # type: ignore
    activo: bool = Field(..., example=True) # type: ignore

class ClienteUpdate(BaseModel):
    nombre: Optional[str] = Field(None, min_length=3, example="Juan Pérez") # type: ignore
    correo: Optional[EmailStr] = Field(None, example="juan@ejemplo.com") # type: ignore
    direccion: Optional[str] = Field(None, example="Calle 123") # type: ignore
    telefono: Optional[str] = Field(None, min_length=10, max_length=10, example="4421234567") # type: ignore
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

#def leer_clientes(include_inactive: bool = True):
def leer_clientes():
    """Lee todos los clientes de la base de datos PostgreSQL.
    """
    conn = get_db_connection()
    try:
        cursor = conn.cursor(cursor_factory=RealDictCursor)
        cursor.execute("SELECT id_cliente, nombre, correo, direccion, telefono, activo FROM clientes ORDER BY id_cliente")
        clientes = cursor.fetchall()
        return [dict(cliente) for cliente in clientes]
    except Exception as e:
        print(f"Error reading clientes: {e}")
        raise HTTPException(status_code=500, detail="Error reading clientes from database")
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
    
    token = create_access_token(data={"sub": credentials.username, "service": "clientes"})
    return {"access_token": token, "token_type": "bearer"}

@app.get(
    "/clientes",
    response_model=List[Cliente],
    tags=["Consultas"],
    summary="Obtener lista de clientes",
    status_code=200,
    responses={
        200: {
            "description": "Lista de clientes obtenida exitosamente",
            "content": {
                "application/json": {
                    "example": [
                        {
                            "id_cliente": 101,
                            "nombre": "Juan Pérez",
                            "correo": "juan@ejemplo.com",
                            "direccion": "Calle 123",
                            "telefono": "4421234567",
                            "activo": "True"
                        }
                    ]
                }
            }
        }
    }
)

#def obtener_clientes(include_inactive: bool = Query(False, description="Si True, incluye clientes inactivos"), token: dict = Depends(verify_token)):
def obtener_clientes(token: dict = Depends(verify_token)):
    """Retorna el padrón oficial de clientes desde la base de datos.
    
    Este endpoint obtiene la lista de clientes registrados en la base de datos.
    
    Args:
        include_inactive: Si True, incluye clientes inactivos. Si False, solo activos.
    
    Returns:
        List[Cliente]: Lista de clientes con todos sus datos.
    """
    return leer_clientes()

@app.post(
    "/clientes",
    tags=["Operaciones"],
    summary="Registrar nuevo cliente",
    status_code=201,
    responses={
        201: {
            "description": "Cliente registrado exitosamente",
            "content": {
                "application/json": {
                    "example": {
                        "mensaje": "Cliente registrado en el archivo CSV",
                        "id_cliente": 1
                    }
                }
            }
        },
        422: {
            "description": "Datos de entrada inválidos o formato incorrecto"
        }
    }
)
def registrar_cliente(nuevo: ClienteRegistro, token: dict = Depends(verify_token)):
    """Registra un nuevo cliente en la base de datos.
    
    Crea un nuevo cliente con el siguiente flujo:
    1. Valida los datos de entrada según el modelo ClienteRegistro
    2. Inserta el registro en la base de datos PostgreSQL
    3. Retorna el ID asignado
    
    Args:
        nuevo (ClienteRegistro): Datos del cliente a registrar.
            - nombre: Nombre del cliente (mínimo 3 caracteres)
            - correo: Email válido del cliente
            - direccion: Dirección del cliente
            - telefono: Teléfono de contacto
    
    Returns:
        dict: Diccionario con mensaje de éxito e ID asignado del cliente.
    """
    conn = get_db_connection()
    try:
        cursor = conn.cursor()
        cursor.execute("""
            INSERT INTO clientes (nombre, correo, direccion, telefono, activo)
            VALUES (%s, %s, %s, %s, %s)
            RETURNING id_cliente
        """, (nuevo.nombre, nuevo.correo, nuevo.direccion, nuevo.telefono, nuevo.activo))
        
        new_id = cursor.fetchone()[0]
        conn.commit()
        return {"mensaje": "Cliente registrado en la base de datos", "id_cliente": new_id, "status": "success"}
    except psycopg2.IntegrityError as e:
        conn.rollback()
        raise HTTPException(status_code=409, detail="El correo ya está registrado")
    except Exception as e:
        conn.rollback()
        print(f"Error registering cliente: {e}")
        raise HTTPException(status_code=500, detail="Error registering cliente in database")
    finally:
        cursor.close()
        return_db_connection(conn)

@app.delete(
    "/clientes/{id_cliente}",
    tags=["Operaciones"],
    summary="Eliminar cliente",
    status_code=200,
    responses={
        200: {
            "description": "Cliente eliminado exitosamente",
            "content": {
                "application/json": {
                    "example": {
                        "mensaje": "Cliente eliminado exitosamente",
                        "status": "success"
                    }
                }
            }
        },
        404: {
            "description": "Cliente no encontrado con el ID especificado",
            "content": {
                "application/json": {
                    "example": {
                        "detail": "Cliente no encontrado"
                    }
                }
            }
        }
    }
)
def eliminar_cliente(id_cliente: int, token: dict = Depends(verify_token)):
    """Elimina un cliente existente de la base de datos.
    
    Marca un cliente como inactivo por su ID único. No se elimina físicamente 
    el registro para evitar orfandad en pedidos que referencian este cliente.
    
    Args:
        id_cliente (int): ID único del cliente a eliminar.
    
    Returns:
        dict: Diccionario con mensaje de confirmación de eliminación.
    
    Raises:
        HTTPException: Con status 404 si el cliente no existe.
    """
    conn = get_db_connection()
    try:
        cursor = conn.cursor()
        
        # Check if cliente exists
        cursor.execute("SELECT id_cliente FROM clientes WHERE id_cliente = %s", (id_cliente,))
        if not cursor.fetchone():
            raise HTTPException(status_code=404, detail="Cliente no encontrado")
        
        # Mark as inactive
        cursor.execute("""
            UPDATE clientes
            SET activo = FALSE, updated_at = CURRENT_TIMESTAMP
            WHERE id_cliente = %s
        """, (id_cliente,))
        
        conn.commit()
        return {"mensaje": "Cliente eliminado (inactivado) exitosamente", "status": "success"}
    except HTTPException:
        raise
    except Exception as e:
        conn.rollback()
        print(f"Error deleting cliente: {e}")
        raise HTTPException(status_code=500, detail="Error deleting cliente from database")
    finally:
        cursor.close()
        return_db_connection(conn)

@app.patch(
    "/clientes/{id_cliente}",
    tags=["Operaciones"],
    summary="Actualizar cliente parcialmente",
    status_code=200,
    responses={
        200: {
            "description": "Cliente actualizado parcialmente de forma exitosa",
            "content": {
                "application/json": {
                    "example": {
                        "mensaje": "Cliente actualizado parcialmente exitosamente",
                        "status": "success"
                    }
                }
            }
        },
        404: {
            "description": "Cliente no encontrado con el ID especificado",
            "content": {
                "application/json": {
                    "example": {
                        "detail": "Cliente no encontrado"
                    }
                }
            }
        },
        422: {
            "description": "Datos de entrada inválidos o formato incorrecto"
        }
    }
)
def actualizar_cliente_parcial(id_cliente: int, update: ClienteUpdate, token: dict = Depends(verify_token)):
    """Actualiza parcialmente un cliente existente.
    
    Permite actualizar uno o más campos de un cliente sin necesidad
    de proporcionar todos los datos. Los campos no proporcionados
    se mantienen sin cambios.
    
    Args:
        id_cliente (int): ID único del cliente a actualizar.
        update (ClienteUpdate): Datos opcionales a actualizar.
            - nombre (opcional): Nuevo nombre (mínimo 3 caracteres)
            - correo (opcional): Nuevo email válido
            - direccion (opcional): Nueva dirección
            - telefono (opcional): Nuevo teléfono de contacto
            - activo (opcional): Nuevo estado de actividad del cliente
    
    Returns:
        dict: Diccionario con mensaje de confirmación de actualización.
    
    Raises:
        HTTPException: Con status 404 si el cliente no existe.
    """
    conn = get_db_connection()
    try:
        cursor = conn.cursor()
        
        # Check if cliente exists
        cursor.execute("SELECT id_cliente FROM clientes WHERE id_cliente = %s", (id_cliente,))
        if not cursor.fetchone():
            raise HTTPException(status_code=404, detail="Cliente no encontrado")
        
        # Build dynamic update query
        updates = []
        params = []
        
        if update.nombre is not None:
            updates.append("nombre = %s")
            params.append(update.nombre)
        if update.correo is not None:
            updates.append("correo = %s")
            params.append(update.correo)
        if update.direccion is not None:
            updates.append("direccion = %s")
            params.append(update.direccion)
        if update.telefono is not None:
            updates.append("telefono = %s")
            params.append(update.telefono)
        if update.activo is not None:
            updates.append("activo = %s")
            params.append(update.activo)
        
        # Add id_cliente to params for WHERE clause
        params.append(id_cliente)
        
        if updates:
            updates.append("updated_at = CURRENT_TIMESTAMP")
            query = f"UPDATE clientes SET {', '.join(updates)} WHERE id_cliente = %s"
            cursor.execute(query, params)
            conn.commit()
        
        return {"mensaje": "Cliente actualizado parcialmente exitosamente", "status": "success"}
    except HTTPException:
        raise
    except psycopg2.IntegrityError as e:
        conn.rollback()
        raise HTTPException(status_code=409, detail="El correo ya está registrado")
    except Exception as e:
        conn.rollback()
        print(f"Error updating cliente: {e}")
        raise HTTPException(status_code=500, detail="Error updating cliente in database")
    finally:
        cursor.close()
        return_db_connection(conn)

