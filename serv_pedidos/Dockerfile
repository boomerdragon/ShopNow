FROM python:3.12
WORKDIR /app
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt
COPY . .
EXPOSE 8002
ENV PRODUCTOS_SERVICE_URL=https://shopnow-productos.onrender.com
ENV INVENTARIO_SERVICE_URL=https://shopnow-inventario.onrender.com
ENV PEDIDOS_SERVICE_URL=https://shopnow-pedidos.onrender.com
ENV CLIENTES_SERVICE_URL=https://shopnow-clientes.onrender.com
CMD ["uvicorn", "serv_pedidos:app", "--host", "0.0.0.0", "--port", "8002"]
