# Render.com Deployment Guide for ShopNow

Complete step-by-step guide to deploy ShopNow microservices to Render.com cloud platform.

## Table of Contents

1. [Overview & Benefits](#overview--benefits)
2. [Prerequisites](#prerequisites)
3. [Account Setup](#account-setup)
4. [Repository Preparation](#repository-preparation)
5. [Render Configuration](#render-configuration)
6. [Service Deployment](#service-deployment)
7. [Environment Variables](#environment-variables)
8. [Post-Deployment Verification](#post-deployment-verification)
9. [Service Communication (HTTP-based)](#service-communication-http-based)
10. [Monitoring & Logs](#monitoring--logs)
11. [Troubleshooting](#troubleshooting)
12. [Cost Management & Scaling](#cost-management--scaling)

---

## Overview & Benefits

### Why Render.com?

- **Fixed Pricing**: Easier to predict costs vs. pay-as-you-go models
- **Reliability**: 99.95% SLA on paid plans
- **Native Docker Support**: Use Dockerfiles directly
- **GitHub Integration**: Auto-deploy on every push
- **Free Tier Available**: Start with small deployments at no cost
- **Simple Configuration**: Single `render.yaml` file defines all services

### Architecture (No RabbitMQ)

```
┌─────────────────────────────────────────────────┐
│           Render.com Cloud Platform             │
├─────────────────────────────────────────────────┤
│                                                 │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐       │
│  │ Clientes │  │Productos │  │  Pedidos │       │
│  │  :8000   │  │  :8001   │  │  :8002   │       │
│  └────┬─────┘  └────┬─────┘  └────┬─────┘       │
│       │             │             │             │
│       └─────────────┼─────────────┘             │
│                     │                           │
│            HTTP REST API Calls                  │
│                     │                           │
│       ┌─────────────┴─────────────┐             │
│       │                           │             │
│  ┌────▼─────┐  ┌──────────────┐   │             │
│  │Inventario│  │Productos-PHP │   │             │
│  │  :8003   │  │   :8081      │   │             │
│  └──────────┘  └──────────────┘   │             │
│                                   │             │
└───────────────────────────────────┴─────────────┘
```

**Key Note**: Services communicate via HTTP REST calls, not message queues. This requires service endpoints to accept direct HTTP requests.

---

## Prerequisites

### Required
- GitHub account with ShopNow repository pushed
- Render.com account (free signup at https://render.com)
- `render.yaml` configuration file - ✅ Already created
- All Dockerfiles in place - ✅ Already created
- Each service with `requirements.txt` (Python) or `composer.json` (PHP) - ✅ Already created

### Recommended
- Render CLI installed (optional, for advanced operations)
- Basic understanding of cloud services and environment variables

### Not Needed
- Docker Desktop running
- RabbitMQ server
- Manual container management
- Redis or PostgreSQL (can add later)

---

## Account Setup

### Step 1: Create Render Account

1. Visit https://render.com
2. Click **"Get Started"** or **"Sign Up"**
3. Choose **"Sign up with GitHub"** (recommended)
4. Authorize Render to access your GitHub account
5. Complete email verification

### Step 2: Verify GitHub Integration

```bash
# After signup, in Render Dashboard:
# 1. Click "Account" in top-right
# 2. Go to "Connected Services"
# 3. Verify GitHub is connected
# 4. Note: You can limit repo access for security
```

---

## Repository Preparation

### Step 1: Verify Files Exist & Are Committed

```bash
# From project root
cd /home/boomer/ITQ/SOA/ShopNow

# Check all required files exist
ls -la render.yaml                    # ✅ Should exist
ls -la serv_*/Dockerfile             # ✅ Should all exist
ls -la serv_*/requirements.txt        # ✅ Python services only
ls -la serv_productos_php/composer.json  # ✅ PHP service only

# Verify git status
git status
```

### Step 2: Commit & Push to GitHub

```bash
# Stage all files (if any new changes)
git add .

# Commit
git commit -m "Add Render.com deployment configuration

- Add render.yaml with all 5 services (no RabbitMQ)
- Python services: Clientes, Productos, Pedidos, Inventario
- PHP service: Productos-PHP
- All services auto-deploy on GitHub push"

# Push to main branch
git push origin main
```

**Important**: Render.com watches your GitHub repository. All changes must be pushed for auto-deployment to work.

---

## Render Configuration

### Step 1: Import Project from GitHub

1. **Log in** to Render Dashboard: https://dashboard.render.com
2. Click **"New +"** button (top-right)
3. Select **"Blueprint"** (this uses `render.yaml`)
4. Click **"Connect Repository"**
5. Search for **"ShopNow"** repository
6. Click **"Connect"** to authorize Render to access it

### Step 2: Configure Blueprint Deployment

1. **Name**: Give your blueprint a name (e.g., "shopnow-microservices")
2. **Branch**: Keep as **"main"** (or your default branch)
3. **Root Directory**: Leave empty (uses project root)
4. Click **"Deploy Blueprint"**

Render will:
- Read `render.yaml`
- Create all 5 services automatically
- Set up deployment pipelines
- Start initial builds

### Step 3: Monitor Initial Deployment

```bash
# In Render Dashboard:
# 1. Watch the "Deployments" tab as services build
# 2. Each service should show a green checkmark when ready
# 3. Initial deployment takes 3-5 minutes per service
```

---

## Environment Variables

### Step 1: Set Service-Specific Variables (Optional)

Each service in `render.yaml` has pre-configured variables:
- `PORT`: Automatically set by Render
- `SERVICE_NAME`: Identifies the service
- `PYTHONUNBUFFERED`: Ensures live log streaming

If your services need custom variables (API keys, JWT secrets, etc.), add them:

1. **In Render Dashboard**:
   - Click on a service
   - Go to **"Environment"** tab
   - Click **"Add Environment Variable"**
   - Add key-value pairs

2. **Example for JWT Secret**:
   ```
   Key: JWT_SECRET
   Value: your-super-secret-key-here
   ```

### Step 2: Share Variables Across Services (Optional)

If multiple services need the same variable:

1. Create variables in the **Blueprint** settings instead of individual services
2. In Render Dashboard:
   - Click your blueprint
   - Go to **"Environment"** tab
   - Add shared variables
   - Services will inherit them automatically

### Step 3: Production Secrets (Recommended)

**Never commit secrets to GitHub**. Instead:

1. **GitHub Actions Secrets** (for CI/CD):
   ```bash
   # In GitHub repo:
   # Settings > Secrets and variables > Actions
   # Add: JWT_SECRET, DB_PASSWORD, API_KEYS, etc.
   ```

2. **Render Secrets** (for runtime):
   - Same as environment variables, but marked as secret
   - Won't appear in logs or dashboards
   - Use for: `JWT_SECRET`, `API_KEYS`, `PASSWORDS`

---

## Service Deployment

### Step 1: Auto-Deployment on Push

Once configured, services auto-deploy whenever you push to GitHub:

```bash
# Make a code change locally
nano serv_clientes/serv_clientes.py

# Commit and push
git add .
git commit -m "Update clientes service"
git push origin main

# Render automatically:
# 1. Receives webhook from GitHub
# 2. Rebuilds affected service(s)
# 3. Deploys to new container
# 4. Keeps previous version as fallback
```

### Step 2: Manual Deployment

To redeploy without code changes:

1. **In Render Dashboard**:
   - Click the service you want to re-deploy
   - Click **"Manual Deploy"** button
   - Select **"Deploy latest commit"**
   - Wait 2-3 minutes for rebuild

### Step 3: Deployment Status

Each service shows:
- 🟢 **Live**: Service is running and healthy
- 🟡 **Building**: Docker image being built
- 🟠 **Deploying**: Container being spun up
- 🔴 **Failed**: Check logs for errors

### Step 4: Access Deployed Services

After deployment, services are accessible at:

```
https://shopnow-clientes.onrender.com/docs      (Swagger UI)
https://shopnow-productos.onrender.com/docs     (Swagger UI)
https://shopnow-pedidos.onrender.com/docs       (Swagger UI)
https://shopnow-inventario.onrender.com/docs    (Swagger UI)
https://shopnow-productos-php.onrender.com      (PHP Service)
```

---

## Post-Deployment Verification

### Step 1: Health Check

```bash
# Test each service endpoint
curl https://shopnow-clientes.onrender.com/docs
curl https://shopnow-productos.onrender.com/docs
curl https://shopnow-pedidos.onrender.com/docs
curl https://shopnow-inventario.onrender.com/docs
curl https://shopnow-productos-php.onrender.com
```

### Step 2: Test API Endpoints

```bash
# Example: Get all clientes
curl https://shopnow-clientes.onrender.com/clientes

# Example: Get all productos
curl https://shopnow-productos.onrender.com/productos

# Example: Get all pedidos
curl https://shopnow-pedidos.onrender.com/pedidos

# Example: Get inventario
curl https://shopnow-inventario.onrender.com/inventario
```

### Step 3: Verify Service Communication

Since there's no RabbitMQ, test service-to-service HTTP calls:

```bash
# Pedidos service calling Clientes service
curl https://shopnow-pedidos.onrender.com/validate-cliente/1

# Pedidos service calling Productos service
curl https://shopnow-pedidos.onrender.com/validate-producto/1
```

---

## Service Communication (HTTP-based)

### Architecture Change: No Messaging Queue

**Before (with RabbitMQ)**:
- Services published messages to queue
- Other services consumed messages asynchronously
- Resilient to temporary service outages

**Now (HTTP-based)**:
- Pedidos service makes direct HTTP calls to other services
- Synchronous communication
- Immediate response required
- If a service is down, the request fails

### Implementation Example

Update your services to use HTTP instead of RabbitMQ:

**serv_pedidos.py** (modified):
```python
import httpx

SERVICE_BASE_URLS = {
    "clientes": "https://shopnow-clientes.onrender.com",
    "productos": "https://shopnow-productos.onrender.com",
    "inventario": "https://shopnow-inventario.onrender.com",
}

async def validate_cliente(cliente_id: int):
    url = f"{SERVICE_BASE_URLS['clientes']}/clientes/{cliente_id}"
    async with httpx.AsyncClient() as client:
        response = await client.get(url)
        return response.json()

async def validate_producto(producto_id: int):
    url = f"{SERVICE_BASE_URLS['productos']}/productos/{producto_id}"
    async with httpx.AsyncClient() as client:
        response = await client.get(url)
        return response.json()

async def update_inventario(producto_id: int, cantidad: int):
    url = f"{SERVICE_BASE_URLS['inventario']}/inventario/{producto_id}"
    async with httpx.AsyncClient() as client:
        response = await client.put(url, json={"cantidad": cantidad})
        return response.json()
```

### Service URLs by Environment

**Local Development**:
```python
SERVICE_BASE_URLS = {
    "clientes": "http://localhost:8000",
    "productos": "http://localhost:8001",
    "inventario": "http://localhost:8003",
}
```

**Render.com Production**:
```python
SERVICE_BASE_URLS = {
    "clientes": "https://shopnow-clientes.onrender.com",
    "productos": "https://shopnow-productos.onrender.com",
    "inventario": "https://shopnow-inventario.onrender.com",
}
```

**Use Environment Variables** for flexibility:
```python
import os

SERVICE_BASE_URLS = {
    "clientes": os.getenv("CLIENTES_URL", "http://localhost:8000"),
    "productos": os.getenv("PRODUCTOS_URL", "http://localhost:8001"),
    "inventario": os.getenv("INVENTARIO_URL", "http://localhost:8003"),
}
```

---

## Monitoring & Logs

### Step 1: Access Service Logs

**In Render Dashboard**:
1. Click on a service
2. Go to **"Logs"** tab
3. Real-time logs stream automatically
4. Filter by timestamp or search keywords

### Step 2: Common Log Locations

```bash
# Application output
STDOUT/STDERR in Render Logs tab

# Build logs
View during "Building" status

# Deployment logs
View in "Deployments" tab
```

### Step 3: Monitor Metrics (Paid Plans)

For **Starter** plan and above:
- CPU usage
- Memory usage
- Request count
- Response times
- Error rates

### Step 4: Set Up Alerts (Paid Plans)

1. Go to service settings
2. Enable **"Alerts"**
3. Configure thresholds:
   - High CPU (>80%)
   - High memory (>90%)
   - Service down (status != 200)
   - Deployment failures

---

## Troubleshooting

### Issue 1: Service Won't Start

**Problem**: Service shows 🔴 Failed status

**Solutions**:
1. Check logs in Render Dashboard
2. Common causes:
   - Port conflict (Render assigns ports dynamically)
   - Missing dependencies in `requirements.txt`
   - Python version mismatch
3. Fix and push to GitHub to redeploy

### Issue 2: Build Fails

**Problem**: "Dockerfile build failed" error

**Solutions**:
1. Verify all `Dockerfile` syntax is correct
2. Check that `requirements.txt` exists in the service directory
3. Ensure all COPY commands in Dockerfile reference correct paths
4. Test build locally: `docker build -f serv_clientes/Dockerfile .`

### Issue 3: Service-to-Service Communication Fails

**Problem**: Pedidos service can't reach Clientes service

**Solutions**:
1. Verify service URLs are correct (use `https://` not `http://`)
2. Check that all services are deployed and showing 🟢 Live
3. Verify endpoint paths exist (e.g., `/clientes/1` vs `/cliente/1`)
4. Add error handling and retry logic:
   ```python
   import httpx
   from tenacity import retry, stop_after_attempt, wait_exponential
   
   @retry(stop=stop_after_attempt(3), wait=wait_exponential())
   async def call_service(url):
       async with httpx.AsyncClient() as client:
           return await client.get(url, timeout=10.0)
   ```

### Issue 4: Slow Deployment

**Problem**: Deployment takes 10+ minutes

**Causes**:
1. Large dependencies being installed
2. Free tier resources limited during peak hours
3. GitHub webhook delay

**Solutions**:
- Optimize `requirements.txt` (use lightweight alternatives)
- Consider upgrading to Starter plan ($7/month) for faster builds
- Wait 30 minutes and retry

### Issue 5: Service Keeps Crashing

**Problem**: Service deploys but crashes after 30 seconds

**Solutions**:
1. **Check environment variables**: Service might need JWT_SECRET or other config
2. **Verify CSV files exist**: Services expect `clientes.csv`, `productos.csv`, etc.
3. **Check logs for stack trace**: Go to Logs tab and search for "Error" or "Exception"
4. **Fix locally first**: Test in local development before pushing

---

## Cost Management & Scaling

### Pricing Overview

| Plan | Cost | RAM | CPU | Auto-deploy |
|------|------|-----|-----|------------|
| Free | $0 | 512 MB | Shared | ✅ Yes |
| Starter | $7/month | 512 MB | 0.5 CPU | ✅ Yes |
| Standard | $12/month | 2 GB | 1 CPU | ✅ Yes |
| Pro | $29/month | 4 GB | 2 CPU | ✅ Yes |

### Ways to Reduce Costs

1. **Consolidate Services**: Combine non-critical services into one
2. **Use Render's Free Tier**: 
   - 750 free instance hours/month (= ~1 small service always running)
   - No credit card required initially
3. **Optimize Images**: 
   - Use Alpine Python: `FROM python:3.9-alpine` (saves ~200 MB)
   - Multi-stage builds
4. **Reduce Idle Services**: Stop services you're not actively developing

### Scaling Configuration

**Current Setup (render.yaml)**:
- All services set to `plan: free`
- Auto-scales based on traffic (free tier is best-effort)

**To Scale Up**:
1. In Render Dashboard, click a service
2. Go to **"Settings"**
3. Change **"Instance Type"**: Free → Starter → Standard
4. Automatic restart with new resources

**Load Balancing**:
- Render automatically load-balances incoming requests
- No additional configuration needed
- Add multiple instances per service (Starter plan+):
  ```yaml
  services:
    - type: web
      name: shopnow-clientes
      numInstances: 3  # Run 3 parallel instances
  ```

---

## Next Steps

### Immediate Actions
1. ✅ Push `render.yaml` to GitHub
2. ✅ Create Render account and connect GitHub
3. ✅ Deploy Blueprint (auto-creates all 5 services)
4. ✅ Test endpoints at `https://shopnow-*.onrender.com`

### Short-term Improvements
- [ ] Update services to use HTTP calls instead of RabbitMQ
- [ ] Add error handling for inter-service timeouts
- [ ] Set up environment variables for service URLs
- [ ] Test service-to-service communication on Render

### Long-term Enhancements
- [ ] Add PostgreSQL for persistent data (replace CSV)
- [ ] Implement Redis for caching
- [ ] Set up monitoring and alerts
- [ ] Upgrade to Starter plan ($7/month) for reliability
- [ ] Re-add RabbitMQ as a Render service if needed

---

## Additional Resources

- **Render Docs**: https://render.com/docs
- **Render CLI**: https://render.com/docs/cli
- **Blueprint Reference**: https://render.com/docs/blueprints
- **Troubleshooting**: https://render.com/docs/troubleshooting

---

## Support

**Having issues?**

1. Check logs in Render Dashboard (Logs tab)
2. Review troubleshooting section above
3. Visit Render Community: https://render.com/community
4. Contact Render Support (Starter plan+)
