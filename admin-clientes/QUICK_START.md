# Quick Start Guide - Admin Clientes Interface

## 🚀 Launch Everything (Recommended)

### Terminal 1 - Start Backend Services

```bash
cd /home/boomer/ITQ/SOA/ShopNow

# Start RabbitMQ container
docker-compose up -d

# Activate Python environment
source .venv/bin/activate

# Start all services
bash shopnow.sh start
```

You should see all services running on ports 8000-8003.

### Terminal 2 - Start Admin Interface

```bash
cd /home/boomer/ITQ/SOA/ShopNow/admin-clientes

# Start PHP built-in web server
php -S localhost:8080
```

### Terminal 3 - (Optional) Monitor Logs

```bash
# Watch for any errors
tail -f *.log 2>/dev/null || echo "No log files yet"
```

## 🌐 Access the Application

Open your browser and visit:

```
http://localhost:8080
```

### Login Credentials

- **Username:** `admin`
- **Password:** `password123`

## 📊 Verify All Services

Once logged in, all services should be accessible:

- **Admin Interface:** http://localhost:8080 (this app)
- **Clientes API:** http://localhost:8000/docs
- **Productos API:** http://localhost:8001/docs
- **Pedidos API:** http://localhost:8002/docs
- **Inventario API:** http://localhost:8003/docs
- **RabbitMQ Dashboard:** http://localhost:15672 (guest/guest)

## 🛑 Stop Everything

```bash
# Terminal 1 - Stop services
bash shopnow.sh stop
docker-compose down

# Terminal 2 - Stop PHP server
Ctrl+C

# Terminal 3
Ctrl+C
```

## 🐛 Troubleshooting

### PHP Server won't start

```bash
# Check if port 8080 is in use
lsof -i :8080

# If in use, kill the process
lsof -ti :8080 | xargs kill -9

# Or use a different port
php -S localhost:8081
```

### Can't connect to Clientes service

```bash
# Verify service is running
curl http://localhost:8000/docs

# If not, start services in Terminal 1
bash shopnow.sh start
```

### Login fails

- Verify Clientes service is running
- Check that the demo credentials haven't been changed
- Check browser console for error messages
- Check PHP error logs

### Forms won't submit

- Verify `config.php` has correct `API_BASE_URL`
- Check browser console (F12) for network errors
- Verify Clientes service is responding to API calls

## 📝 Notes

- The interface works best in modern browsers (Chrome, Firefox, Safari, Edge)
- Session expires after 1 hour of inactivity
- All passwords are sent to the Clientes service (verify HTTPS in production)
- Customer emails must be unique in the system

## 🔧 For Development

To test API endpoints directly:

```bash
# Get JWT token
curl -X POST http://localhost:8000/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"password123"}'

# Use token to get customers
curl http://localhost:8000/clientes \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

## 📚 Learn More

- See [README.md](./README.md) for detailed documentation
- Check `/home/boomer/ITQ/SOA/ShopNow/` for service documentation
- Review the attached Copilot instructions for architecture details
