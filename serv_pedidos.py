import csv
import os
import requests
from fastapi import FastAPI, HTTPException
from pydantic import BaseModel, Field
from typing import List


app = FastAPI(
    title="Coordinador de Pedidos",
    description="Servicio encargado de la coordinación y gestión de pedidos de venta, con validación de clientes e inventario. \n\n" \
    "Este servicio actúa como el punto central de integración entre los departamentos de Clientes, Productos e Inventario para garantizar la correcta ejecución de las ventas. \n\n" \
    "Ejecutar en puerto **8002** y asegurarse de que los servicios de Clientes (8000), Productos (8001) e Inventario (8003) estén activos para su correcto funcionamiento. \n\n" \
    "**Versión HTTP**: Versión simplificada sin RabbitMQ, usando comunicación HTTP con otros servicios.",
    version="2.0.0",
    contact={
        "name": "Arturo Barajas, Profesor de SOA - TecNM Querétaro",
    }
)

FILE_NAME = "pedidos.csv"
HEADERS = ["id_pedido", "id_cliente", "id_producto", "cantidad"]

if not os.path.exists(FILE_NAME):
    with open(FILE_NAME, "w", newline="", encoding="utf-8") as f:
        csv.writer(f).writerow(HEADERS)

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

def leer_pedidos():
    """Lee todos los pedidos del archivo CSV con conversión de tipos correcta."""
    with open(FILE_NAME, "r", encoding="utf-8") as f:
        rows = csv.DictReader(f)
        pedidos = []
        for row in rows:
            if not row or not row.get('id_pedido'):  # Skip empty rows
                continue
            try:
                pedido = {
                    'id_pedido': int(row['id_pedido']),
                    'id_cliente': int(row['id_cliente']),
                    'id_producto': int(row['id_producto']),
                    'cantidad': int(row['cantidad'])
                }
                pedidos.append(pedido)
            except (ValueError, KeyError):
                continue  # Skip rows with invalid data
        return pedidos

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
def obtener_pedidos():
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
def crear_pedido(p: PedidoRegistro):
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
            response = requests.get(f"{PRODUCTOS_URL}/productos", timeout=5)
            productos = response.json()
            existe_producto = any(prod['id_producto'] == p.id_producto for prod in productos)
            if not existe_producto:
                raise HTTPException(status_code=400, detail="Producto no existe en el catálogo")
        except Exception as e:
            print(f"Error consultando productos: {e}")
            raise HTTPException(status_code=503, detail="No se puede conectar al servicio de Productos")
        
        # PASO 2: Validar que hay inventario suficiente
        try:
            response = requests.get(f"{INVENTARIO_URL}/inventario/{p.id_producto}", timeout=5)
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
        
        # PASO 3: Validar que el cliente existe
        try:
            response = requests.get(f"{CLIENTES_URL}/clientes", timeout=5)
            clientes = response.json()
            existe_cliente = any(cli['id_cliente'] == p.id_cliente for cli in clientes)
            if not existe_cliente:
                raise HTTPException(status_code=400, detail="El cliente no existe en el padrón oficial")
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
                timeout=5
            )
            if response.status_code != 200:
                raise HTTPException(status_code=503, detail="Error al descontar inventario")
        except Exception as e:
            print(f"Error descuentan inventario: {e}")
            raise HTTPException(status_code=503, detail="No se puede descontar inventario")
        
        # PASO 5: Persistir pedido localmente
        pedidos = leer_pedidos()
        if pedidos and len(pedidos) > 0:
            siguiente_id = max(ped['id_pedido'] for ped in pedidos) + 1
        else:
            siguiente_id = 1
        
        with open(FILE_NAME, "a", newline="", encoding="utf-8") as f:
            csv.writer(f).writerow([siguiente_id, p.id_cliente, p.id_producto, p.cantidad])
        
        return {"mensaje": "Venta completada y stock descontado", "id_pedido": siguiente_id, "status": "success"}

    except HTTPException:
        raise
    except Exception as e:
        print(f"Error en crear_pedido: {e}")
        raise HTTPException(status_code=503, detail="Error de comunicación con servicios")
