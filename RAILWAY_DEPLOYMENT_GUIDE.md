# Railway.app Deployment Guide for ShopNow

Complete step-by-step guide to deploy ShopNow microservices to Railway.app cloud platform.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Account Setup](#account-setup)
3. [Repository Preparation](#repository-preparation)
4. [Railway Configuration](#railway-configuration)
5. [Environment Variables](#environment-variables)
6. [Service Deployment](#service-deployment)
7. [Verification & Testing](#verification--testing)
8. [Monitoring & Logs](#monitoring--logs)
9. [Troubleshooting](#troubleshooting)
10. [Scaling & Cost Management](#scaling--cost-management)

---

## Prerequisites

### Required
- GitHub account with ShopNow repository pushed
- Railway.app account (free signup at https://railway.app)
- Docker Compose configuration (`docker-compose.yml`) - ✅ Already created
- All Dockerfiles in place - ✅ Already created

### Recommended
- Railway CLI installed (optional, for advanced operations)
- Basic understanding of microservices architecture

### Not Needed
- Docker Desktop running
- Local RabbitMQ server
- Manual container management

---

## Account Setup

### Step 1: Create Railway Account

```bash
# Visit https://railway.app
# Click "Start Project"
# Login with GitHub (recommended for auto-deployment)
# Grant necessary permissions to your GitHub repositories
```

### Step 2: GitHub Connection

```bash
# In Railway Dashboard:
# 1. Navigate to Account Settings
# 2. Go to GitHub Integration
# 3. Select "Install GitHub App"
# 4. Choose your GitHub organization/user
# 5. Select ShopNow repository (or all repositories)
# 6. Authorize the integration
```

---

## Repository Preparation

### Step 1: Ensure Code is Committed & Pushed

```bash
# From project root
cd /home/boomer/ITQ/SOA/ShopNow

# Check git status
git status

# Stage all changes
git add .

# Commit changes
git commit -m "Prepare ShopNow for Railway deployment

- Add docker-compose.yml for multi-service orchestration
- Fix all Dockerfiles (correct ports and services)
- Add railway.json configuration
- Add GitHub Actions CI/CD workflow
- Add deployment documentation"

# Push to GitHub
git push origin main
```

### Step 2: Verify Repository Structure

```bash
# Ensure these files exist at root:
ls -la | grep -E "(docker-compose|railway|\.github)"

# Expected output:
# docker-compose.yml       ✅
# railway.json            ✅
# .github/workflows/      ✅
```

---

## Railway Configuration

### Step 1: Create New Project in Railway

```bash
# Option A: Via Web UI (Easiest)
# 1. Go to https://railway.app/dashboard
# 2. Click "New Project"
# 3. Select "Deploy from GitHub"
# 4. Find and select your ShopNow repository
# 5. Auto-detect docker-compose.yml (should happen by default)

# Option B: Via Railway CLI
railway login                    # Authenticate
railway init                     # Create new project
railway up                       # Deploy
```

### Step 2: Configure Project

```bash
# In Railway Dashboard:
# Project Settings > Name
# Set Project Name: shopnow-production

# Project Settings > Environment
# Create environments for:
# - production (deploy on main branch)
# - staging (optional, for testing)
```

### Step 3: Connect GitHub Repository

```bash
# Settings > Integrations > GitHub
# Repository: your-username/ShopNow
# Branch to deploy: main
# Auto-deploy: Enable
# Deploy on commit: Enable
```

---

## Environment Variables

### Step 1: Configure Service Environment Variables

Railway automatically uses environment variables from `docker-compose.yml`, but you can override them.

```bash
# In Railway Dashboard for each service:
# 1. Select service
# 2. Click "Variables"
# 3. Add/modify as needed
```

### Step 2: Service-Specific Variables

#### RabbitMQ
```bash
RABBITMQ_DEFAULT_USER=guest
RABBITMQ_DEFAULT_PASS=guest
```

#### Python Services (Clientes, Productos, Pedidos, Inventario)
```bash
RABBITMQ_HOST=rabbitmq              # Railway auto-resolves container names
RABBITMQ_PORT=5672
RABBITMQ_USER=guest
RABBITMQ_PASS=guest

# Optional: JWT Configuration
SECRET_KEY=shopnow-secret-key-2024-change-in-production
ALGORITHM=HS256
EXPIRATION_MINUTES=480
```

#### PHP Service (Productos PHP)
```bash
RABBITMQ_HOST=rabbitmq
RABBITMQ_PORT=5672
RABBITMQ_USER=guest
RABBITMQ_PASS=guest
```

### Step 3: Production Security

⚠️ **Important for Production:**

```bash
# Change these in Railway dashboard (NOT in code):
SECRET_KEY=your-strong-secret-key-here-64-chars-minimum

# Generate secure key:
python3 -c "import secrets; print(secrets.token_urlsafe(32))"
```

---

## Service Deployment

### Step 1: Initial Deployment

```bash
# Railway auto-deploys when it detects:
# 1. docker-compose.yml at repository root ✅
# 2. Dockerfiles in service directories ✅
# 3. Push to main branch ✅

# Monitor deployment in Railway Dashboard:
# Dashboard > Deployments
# Watch logs in real-time
```

### Step 2: Service Startup Sequence

Railway deploys services in this order (based on `depends_on`):

1. **RabbitMQ** (15-30 seconds startup)
2. **Other services** (5-10 seconds each, wait for RabbitMQ)

Expected total deployment time: **3-5 minutes**

### Step 3: Verify Deployment Status

```bash
# In Railway Dashboard:
# Dashboard > Services
# Check status for each:
# - rabbitmq: "Running" (green)
# - clientes: "Running" (green)
# - productos: "Running" (green)
# - pedidos: "Running" (green)
# - inventario: "Running" (green)
```

---

## Verification & Testing

### Step 1: Access Service URLs

After deployment, Railway assigns public URLs to each service:

```bash
# In Railway Dashboard > Service Settings
# Each service shows its public URL

# Format: https://{service-name}-{project-id}.railway.app
# Example:
# - https://clientes-abc123.railway.app:8000/docs
# - https://productos-abc123.railway.app:8001/docs
# - https://pedidos-abc123.railway.app:8002/docs
# - https://inventario-abc123.railway.app:8003/docs
# - https://rabbitmq-abc123.railway.app:15672
```

### Step 2: Test API Endpoints

```bash
# Clientes Service
curl -X GET https://clientes-{id}.railway.app:8000/docs

# Productos Service
curl -X GET https://productos-{id}.railway.app:8001/docs

# Test with actual requests:
curl -X GET https://clientes-{id}.railway.app:8000/clientes
curl -X GET https://productos-{id}.railway.app:8001/productos
```

### Step 3: Test RabbitMQ Connectivity

```bash
# Access RabbitMQ Management UI
# https://rabbitmq-{id}.railway.app:15672
# Username: guest
# Password: guest

# Verify:
# 1. Exchanges tab > 'servicios' exchange exists
# 2. Queues tab > All service queues present
# 3. Connections tab > Services connected
```

### Step 4: Test Inter-Service Communication

Create and submit an order to test message flow:

```bash
# Create order in Pedidos (should trigger Clientes/Productos/Inventario)
curl -X POST https://pedidos-{id}.railway.app:8002/pedidos \
  -H "Content-Type: application/json" \
  -d '{
    "id_cliente": 1,
    "id_producto": 1,
    "cantidad": 5
  }' \
  -H "Authorization: Bearer {jwt-token}"

# Check RabbitMQ for messages (in management UI)
# Verify order processed in pedidos.csv
```

---

## Monitoring & Logs

### Step 1: Access Real-Time Logs

```bash
# In Railway Dashboard:
# 1. Select service
# 2. Click "Logs" tab
# 3. Logs stream in real-time

# Or via Railway CLI:
railway logs clientes
railway logs productos
railway logs pedidos
railway logs inventario
railway logs rabbitmq
```

### Step 2: Common Log Checks

```bash
# ✅ Successful startup:
# "Application startup complete"
# "Uvicorn running on 0.0.0.0:8000"

# ⚠️ RabbitMQ connection issues:
# "failed to connect to RabbitMQ"
# "retrying connection"

# ⚠️ Missing auth.py:
# "ModuleNotFoundError: No module named 'auth'"
```

### Step 3: Monitor Resource Usage

```bash
# In Railway Dashboard > Metrics:
# - CPU usage
# - Memory consumption
# - Network I/O
# - Request count (for web services)

# Alerts available for:
# - Memory threshold
# - CPU threshold
# - Service crashes
```

---

## Troubleshooting

### Problem: Services Not Starting

**Symptoms:** All services show "Crashed" or "Failed"

**Solutions:**
```bash
# 1. Check logs
# Dashboard > Service > Logs
# Look for error messages

# 2. Common causes:
# - RabbitMQ not ready (wait 30 seconds)
# - Port conflicts (unlikely in Railway)
# - Missing dependencies in requirements.txt

# 3. Redeploy
# Dashboard > Deployments > Redeploy latest
```

### Problem: RabbitMQ Connection Failed

**Symptoms:** Services show "retrying connection" in logs

**Solutions:**
```bash
# 1. Verify RabbitMQ is running
# Dashboard > rabbitmq service > Logs
# Should show "Ready to accept connections"

# 2. Check environment variables
# Service > Variables
# RABBITMQ_HOST=rabbitmq (correct)
# RABBITMQ_PORT=5672 (correct)

# 3. Verify network connectivity
# Service > Deploy logs
# Check for network errors
```

### Problem: 502 Bad Gateway on Service URL

**Symptoms:** Accessing service URL returns 502 error

**Solutions:**
```bash
# 1. Service not fully started
# Wait 30-60 seconds
# Refresh browser

# 2. Service crashed
# Check logs in Dashboard
# Redeploy if needed

# 3. Memory limit exceeded
# Dashboard > Service > Settings > Memory
# Increase from default 512MB to 1GB
```

### Problem: CSV Files Empty After Deployment

**Symptoms:** No data persists, files are empty after restart

**Solutions:**
```bash
# 1. CSV files are in container filesystem
# Restart = fresh state

# 2. Solutions:
# Option A: Use persistent volumes (advanced)
# Option B: Use cloud database (PostgreSQL on Railway)
# Option C: Expect reset on restart (for teaching)

# For now with docker-compose:
# Data persists between service restarts
# Data resets on full project redeploy
```

### Problem: JWT Token Issues

**Symptoms:** "Invalid token" or "Token expired" errors

**Solutions:**
```bash
# 1. Verify SECRET_KEY is set
# Dashboard > Service > Variables > SECRET_KEY

# 2. Check token expiration
# EXPIRATION_MINUTES=480 (8 hours by default)

# 3. Regenerate token
# Call /login endpoint in clientes service
# Use new token in Authorization header
```

---

## Scaling & Cost Management

### Step 1: Monitor Free Tier Usage

```bash
# In Railway Dashboard > Billing
# Track:
# - Memory-hours used
# - Network egress
# - Build minutes used

# Free tier includes: $5/month
# Typical ShopNow usage: ~$2-3/month
```

### Step 2: Cost Optimization

```bash
# Keep free tier usage under $5:
# 1. Stop unused services (set to 0 replicas)
# 2. Reduce memory if possible (512MB minimum)
# 3. Remove old deployments
# 4. Use github-actions to pause on inactivity

# Memory allocation per service:
# - RabbitMQ: 512MB (minimum)
# - Each Python service: 512MB
# - PHP service: 256MB
```

### Step 3: Scaling Resources

```bash
# In Railway Dashboard > Service Settings:
# Memory: 512MB > 1GB (if services crash)
# Replicas: 1 > 2+ (for load testing)
# Auto-scaling: Available with paid plan
```

---

## Continuous Deployment

### Automatic Deployment on Push

Railway automatically deploys when:
```bash
git push origin main
```

**Process:**
1. GitHub webhook triggers Railway
2. Railway pulls latest code
3. Docker images rebuild
4. New containers start
5. Load balanced switchover
5. Old containers stop

**Deployment time:** ~3-5 minutes per push

### Disable Auto-Deployment (if needed)

```bash
# Railway Dashboard > Project Settings
# Disable "Auto deploy on commit"
# Then manually trigger via Dashboard
```

---

## Advanced: Environment Files

Create and use `.env.production` for sensitive data:

```bash
# .env.production (don't commit to git)
SECRET_KEY=your-very-secure-key-here
ALGORITHM=HS256
EXPIRATION_MINUTES=480
RABBITMQ_USER=guest
RABBITMQ_PASS=guest
```

Load in Railway as individual variables (copy-paste values).

---

## Rollback Deployment

If deployment has issues:

```bash
# In Railway Dashboard > Deployments
# 1. Find previous successful deployment
# 2. Click "Rollback"
# 3. Old version restarts immediately

# Services revert to previous state
# CSV data persists (if using volumes)
```

---

## Summary Checklist

- [ ] GitHub account with ShopNow repository
- [ ] Railway.app account created
- [ ] GitHub repository connected to Railway
- [ ] `docker-compose.yml` at project root
- [ ] All Dockerfiles validated and fixed
- [ ] `railway.json` configuration file added
- [ ] Environment variables configured
- [ ] Project deployed successfully
- [ ] All services running (green status)
- [ ] API endpoints accessible
- [ ] RabbitMQ management UI working
- [ ] Services communicating via RabbitMQ
- [ ] Logs monitored and verified
- [ ] GitHub Actions CI/CD pipeline active
- [ ] Cost monitoring setup

---

## Test Commands (Post-Deployment)

```bash
# Get your Railway project URL (from dashboard)
RAILWAY_URL="https://your-project.railway.app"

# Test Clientes service
curl -s ${RAILWAY_URL}/clientes/docs | head -20

# Test all services health
for service in clientes productos pedidos inventario; do
  echo "Testing $service..."
  curl -s https://${service}-abc123.railway.app:800X/docs
done

# Test RabbitMQ connectivity
curl -s https://rabbitmq-abc123.railway.app:15672/api/overview \
  -u guest:guest
```

---

## Support & Resources

- **Railway Documentation:** https://docs.railway.app
- **Docker Compose Deployment:** https://docs.railway.app/deploy/dockercompose
- **Environment Variables:** https://docs.railway.app/develop/variables
- **GitHub Integration:** https://docs.railway.app/develop/github
- **Community Discord:** https://railway.app/discord

---

## Next Steps

1. ✅ Deploy to Railway following this guide
2. ✅ Test all microservices
3. ✅ Monitor logs and health
4. Consider adding:
   - PostgreSQL database (instead of CSV)
   - Redis for caching
   - Monitoring/alerting
   - Custom domain
   - SSL certificate (free with Railway)

---

**Last Updated:** April 16, 2026
**For:** ShopNow Microservices Project v1.0
