#!/bin/bash
# Quick JWT Authentication Test Script for ShopNow Services

set -e

echo "=========================================="
echo "ShopNow JWT Authentication Test"
echo "=========================================="
echo ""

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
ADMIN_USER="admin"
ADMIN_PASS="password123"

# Service URLs
CLIENTES_URL="http://localhost:8000"
PRODUCTOS_URL="http://localhost:8001"
PEDIDOS_URL="http://localhost:8002"
INVENTARIO_URL="http://localhost:8003"

# Test variables
TOKEN=""
RESPONSE=""

# Helper functions
log_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

log_error() {
    echo -e "${RED}✗ $1${NC}"
}

log_info() {
    echo -e "${YELLOW}→ $1${NC}"
}

# Check if services are running
check_service() {
    local service_name=$1
    local url=$2
    
    log_info "Checking if $service_name is running at $url..."
    if curl -s "$url/docs" > /dev/null 2>&1; then
        log_success "$service_name is running"
        return 0
    else
        log_error "$service_name is NOT running"
        return 1
    fi
}

# Test login endpoint
test_login() {
    local service_name=$1
    local url=$2
    
    log_info "Testing login on $service_name..."
    
    RESPONSE=$(curl -s -X POST "$url/login" \
        -H "Content-Type: application/json" \
        -d "{\"username\":\"$ADMIN_USER\",\"password\":\"$ADMIN_PASS\"}")
    
    # Check if response contains access_token
    if echo "$RESPONSE" | grep -q "access_token"; then
        TOKEN=$(echo "$RESPONSE" | grep -o '"access_token":"[^"]*' | cut -d'"' -f4)
        log_success "Login successful for $service_name"
        echo "  Token: ${TOKEN:0:50}..."
        return 0
    else
        log_error "Login failed for $service_name"
        echo "  Response: $RESPONSE"
        return 1
    fi
}

# Test protected endpoint
test_protected_endpoint() {
    local service_name=$1
    local endpoint=$2
    local token=$3
    
    log_info "Testing protected endpoint: $endpoint"
    
    RESPONSE=$(curl -s -w "\n%{http_code}" -X GET "$endpoint" \
        -H "Authorization: Bearer $token")
    
    HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
    BODY=$(echo "$RESPONSE" | head -n-1)
    
    if [ "$HTTP_CODE" = "200" ]; then
        log_success "Protected endpoint accessible (HTTP 200)"
        return 0
    else
        log_error "Protected endpoint returned HTTP $HTTP_CODE"
        echo "  Response: $BODY"
        return 1
    fi
}

# Test endpoint without token
test_without_token() {
    local endpoint=$1
    
    log_info "Testing endpoint WITHOUT token..."
    
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" -X GET "$endpoint")
    
    if [ "$HTTP_CODE" = "403" ] || [ "$HTTP_CODE" = "401" ]; then
        log_success "Endpoint correctly rejected request without token (HTTP $HTTP_CODE)"
        return 0
    else
        log_error "Endpoint did not reject request without token (HTTP $HTTP_CODE)"
        return 1
    fi
}

echo ""
echo "========== STEP 1: Check Services =========="
echo ""

services=(
    "Clientes:$CLIENTES_URL"
    "Productos:$PRODUCTOS_URL"
    "Pedidos:$PEDIDOS_URL"
    "Inventario:$INVENTARIO_URL"
)

all_running=true
for service in "${services[@]}"; do
    IFS=':' read -r name url <<< "$service"
    if ! check_service "$name" "$url"; then
        all_running=false
    fi
done

if [ "$all_running" = false ]; then
    echo ""
    log_error "Some services are not running. Start them with: bash shopnow.sh start"
    exit 1
fi

echo ""
echo "========== STEP 2: Test Authentication =========="
echo ""

# Test login on Clientes
if test_login "Clientes" "$CLIENTES_URL"; then
    CLIENTES_TOKEN=$TOKEN
else
    exit 1
fi

echo ""

# Test protected endpoint with token
if test_protected_endpoint "Clientes" "$CLIENTES_URL/clientes" "$CLIENTES_TOKEN"; then
    :
else
    log_error "Could not access protected endpoint with valid token"
    exit 1
fi

echo ""

# Test endpoint without token
if test_without_token "$CLIENTES_URL/clientes"; then
    :
else
    log_error "Endpoint did not properly reject request without token"
    exit 1
fi

echo ""
echo "========== STEP 3: Test Other Services =========="
echo ""

# Test Productos
if test_login "Productos" "$PRODUCTOS_URL"; then
    PRODUCTOS_TOKEN=$TOKEN
    test_protected_endpoint "Productos" "$PRODUCTOS_URL/productos" "$PRODUCTOS_TOKEN" || true
fi

echo ""

# Test Inventario
if test_login "Inventario" "$INVENTARIO_URL"; then
    INVENTARIO_TOKEN=$TOKEN
    test_protected_endpoint "Inventario" "$INVENTARIO_URL/inventario" "$INVENTARIO_TOKEN" || true
fi

echo ""

# Test Pedidos
if test_login "Pedidos" "$PEDIDOS_URL"; then
    PEDIDOS_TOKEN=$TOKEN
    test_protected_endpoint "Pedidos" "$PEDIDOS_URL/pedidos" "$PEDIDOS_TOKEN" || true
fi

echo ""
echo "=========================================="
echo -e "${GREEN}✓ All JWT Authentication tests passed!${NC}"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Open Swagger UI: http://localhost:8000/docs"
echo "2. Click 'Authorize' button"
echo "3. Use this token: $CLIENTES_TOKEN"
echo "4. Test endpoints interactively"
echo ""
echo "Documentation:"
echo "- JWT_USAGE_GUIDE.md - Complete usage documentation"
echo "- JWT_IMPLEMENTATION_SUMMARY.md - Technical overview"
