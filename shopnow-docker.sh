#!/bin/bash

###############################################################################
# ShopNow Docker Management Script
# Usage: bash shopnow-docker.sh <command> [options]
###############################################################################

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_NAME="shopnow"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Functions
print_header() {
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_info() {
    echo -e "${YELLOW}ℹ $1${NC}"
}

# Help menu
show_help() {
    cat << EOF
${BLUE}ShopNow Docker Management${NC}

${GREEN}USAGE:${NC}
    bash shopnow-docker.sh <command> [options]

${GREEN}COMMANDS:${NC}
    ${YELLOW}Build & Start${NC}
    build               Build all Docker images
    build <service>     Build specific service (clientes|productos|pedidos|inventario|productos-php)
    up                  Start all services (build if needed)
    up-detach           Start all services in background
    up-prod             Start all services without --reload (production mode)
    up-with-php         Start all services including PHP variant

    ${YELLOW}Stop & Clean${NC}
    down                Stop and remove all containers
    stop                Stop all containers (keep volumes)
    restart             Restart all services
    restart <service>   Restart specific service
    clean               Stop all containers and remove volumes
    clean-all           Complete cleanup (containers, images, volumes, networks)

    ${YELLOW}Status & Logs${NC}
    ps                  List running containers
    logs                View all service logs
    logs <service>      View logs for specific service (e.g., logs clientes)
    status              Show detailed status of all services
    health              Check health status of container services

    ${YELLOW}Access & Testing${NC}
    shell <service>     Enter container shell
    exec <service> <cmd> Execute command in service container
    test-connectivity   Test network connectivity between containers
    test-api            Test all API endpoints
    open-urls           Open all service URLs in browser

    ${YELLOW}Development${NC}
    rebuild <service>   Rebuild specific service (no cache)
    rebuild-all         Rebuild all services (no cache)
    push-images         Push images to registry (requires REGISTRY env var)
    compose <args>      Run docker compose with additional arguments

    ${YELLOW}Network & Volumes${NC}
    network-inspect     Inspect shopnow-network details
    volumes-ls          List all volumes
    volumes-clean       Remove unused volumes

    ${YELLOW}Debugging${NC}
    docker-ps           List all containers (including stopped)
    docker-images       List all images
    docker-stats        Show real-time container statistics
    docker-prune        Clean up unused Docker resources

    ${YELLOW}Utilities${NC}
    version             Show Docker and Docker Compose versions
    help                Show this help message

${GREEN}EXAMPLES:${NC}
    bash shopnow-docker.sh up               # Start all services
    bash shopnow-docker.sh logs clientes    # View clientes logs
    bash shopnow-docker.sh shell productos  # Enter productos container
    bash shopnow-docker.sh rebuild-all      # Rebuild all images
    bash shopnow-docker.sh clean            # Clean everything

${GREEN}SERVICE PORTS:${NC}
    Clientes    : http://localhost:8000/docs
    Productos   : http://localhost:8001/docs
    Pedidos     : http://localhost:8002/docs
    Inventario  : http://localhost:8003/docs
    Productos-PHP : http://localhost:8081
    RabbitMQ    : http://localhost:15672 (guest/guest)

EOF
}

# Build commands
cmd_build() {
    if [ -n "$1" ]; then
        print_header "Building $1 image..."
        docker compose build "$1"
        print_success "$1 image built successfully"
    else
        print_header "Building all images..."
        docker compose build
        print_success "All images built successfully"
    fi
}

# Start commands
cmd_up() {
    print_header "Starting all services..."
    docker compose up -d
    print_success "All services started"
    sleep 3
    cmd_ps
    cmd_health
}

cmd_up_detach() {
    print_header "Starting all services (detached)..."
    docker compose up -d --build
    print_success "All services started in background"
}

cmd_up_prod() {
    print_header "Starting services in production mode (no --reload)..."
    docker compose -f docker compose.yml up -d
    print_success "Services started in production mode"
}

cmd_up_with_php() {
    print_header "Starting all services including PHP variant..."
    docker compose --profile php up -d
    print_success "All services started (including PHP)"
    sleep 3
    cmd_ps
}

# Stop commands
cmd_down() {
    print_header "Stopping and removing containers..."
    docker compose down
    print_success "All services stopped and removed"
}

cmd_stop() {
    print_header "Stopping containers (keeping volumes)..."
    docker compose stop
    print_success "All services stopped"
}

cmd_restart() {
    if [ -n "$1" ]; then
        print_header "Restarting $1..."
        docker compose restart "$1"
        print_success "$1 restarted"
    else
        print_header "Restarting all services..."
        docker compose restart
        print_success "All services restarted"
    fi
}

# Clean commands
cmd_clean() {
    print_header "Cleaning up (removing containers and volumes)..."
    docker compose down -v
    print_success "Cleanup complete"
}

cmd_clean_all() {
    print_header "Complete cleanup - removing containers, images, volumes, and networks..."
    echo -e "${YELLOW}WARNING: This will remove all Docker resources for this project!${NC}"
    read -p "Continue? (y/N) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        docker compose down -v --remove-orphans
        docker image prune -a --filter "label!=keep" -f
        docker network prune -f
        docker volume prune -f
        print_success "Complete cleanup finished"
    else
        print_info "Cleanup cancelled"
    fi
}

# Status & logs commands
cmd_ps() {
    print_header "Container Status"
    docker compose ps
}

cmd_logs() {
    if [ -n "$1" ]; then
        docker compose logs -f "$1"
    else
        docker compose logs -f
    fi
}

cmd_status() {
    print_header "Detailed Service Status"
    docker compose ps
    echo ""
    print_info "RabbitMQ Status:"
    docker compose exec -T rabbitmq rabbitmq-diagnostics -q ping && print_success "RabbitMQ is healthy" || print_error "RabbitMQ is not responding"
}

cmd_health() {
    print_header "Health Check"
    services=("clientes" "productos" "pedidos" "inventario" "rabbitmq")
    
    for service in "${services[@]}"; do
        if docker compose ps "$service" | grep -q "Up"; then
            print_success "$service is running"
            # Try to check actual health
            case $service in
                rabbitmq)
                    if docker compose exec -T rabbitmq rabbitmq-diagnostics -q ping >/dev/null 2>&1; then
                        print_success "  └─ RabbitMQ is responding"
                    fi
                    ;;
                *)
                    # For FastAPI services, check if port is listening
                    port=""
                    case $service in
                        clientes) port="8000" ;;
                        productos) port="8001" ;;
                        pedidos) port="8002" ;;
                        inventario) port="8003" ;;
                    esac
                    if [ -n "$port" ] && docker compose exec -T "$service" bash -c "echo > /dev/tcp/localhost/$port" >/dev/null 2>&1; then
                        print_success "  └─ Port $port is listening"
                    fi
                    ;;
            esac
        else
            print_error "$service is not running"
        fi
    done
}

# Access commands
cmd_shell() {
    if [ -z "$1" ]; then
        print_error "Service name required. Usage: bash shopnow-docker.sh shell <service>"
        exit 1
    fi
    print_info "Entering $1 container shell..."
    docker compose exec "$1" bash
}

cmd_exec() {
    if [ -z "$1" ] || [ -z "$2" ]; then
        print_error "Usage: bash shopnow-docker.sh exec <service> <command>"
        exit 1
    fi
    shift
    docker compose exec "$1" bash -c "$@"
}

cmd_test_connectivity() {
    print_header "Testing Network Connectivity"
    services=("clientes" "productos" "pedidos" "inventario" "rabbitmq")
    
    for service in "${services[@]}"; do
        echo ""
        print_info "Testing from $service container:"
        for target in "${services[@]}"; do
            if [ "$service" != "$target" ]; then
                if docker compose exec -T "$service" bash -c "nc -zv $target 5672 >/dev/null 2>&1 || nc -zv $target 8000 >/dev/null 2>&1 || ping -c 1 $target >/dev/null 2>&1"; then
                    print_success "  ✓ Can reach $target"
                else
                    print_error "  ✗ Cannot reach $target"
                fi
            fi
        done
    done
}

cmd_test_api() {
    print_header "Testing API Endpoints"
    
    endpoints=(
        "http://localhost:8000/docs|Clientes"
        "http://localhost:8001/docs|Productos"
        "http://localhost:8002/docs|Pedidos"
        "http://localhost:8003/docs|Inventario"
        "http://localhost:15672|RabbitMQ"
    )
    
    for endpoint in "${endpoints[@]}"; do
        IFS='|' read -r url name <<< "$endpoint"
        if curl -s -o /dev/null -w "%{http_code}" "$url" | grep -q "200\|401"; then
            print_success "$name - OK"
        else
            print_error "$name - FAILED"
        fi
    done
}

cmd_open_urls() {
    print_info "Opening service URLs..."
    urls=(
        "http://localhost:8000/docs"
        "http://localhost:8001/docs"
        "http://localhost:8002/docs"
        "http://localhost:8003/docs"
        "http://localhost:15672"
    )
    
    for url in "${urls[@]}"; do
        if command -v xdg-open &> /dev/null; then
            xdg-open "$url" &
        elif command -v open &> /dev/null; then
            open "$url" &
        else
            echo "URL: $url"
        fi
    done
}

# Development commands
cmd_rebuild() {
    if [ -z "$1" ]; then
        print_error "Service name required. Usage: bash shopnow-docker.sh rebuild <service>"
        exit 1
    fi
    print_header "Rebuilding $1 (no cache)..."
    docker compose build --no-cache "$1"
    print_success "$1 rebuilt"
}

cmd_rebuild_all() {
    print_header "Rebuilding all services (no cache)..."
    docker compose build --no-cache
    print_success "All services rebuilt"
}

cmd_push_images() {
    if [ -z "$REGISTRY" ]; then
        print_error "REGISTRY environment variable not set. Usage: REGISTRY=myregistry bash shopnow-docker.sh push-images"
        exit 1
    fi
    print_header "Pushing images to $REGISTRY..."
    docker compose push
    print_success "Images pushed successfully"
}

cmd_compose() {
    docker compose "$@"
}

# Network commands
cmd_network_inspect() {
    print_header "Network Details"
    docker network inspect "${PROJECT_NAME}-network" | python3 -m json.tool
}

cmd_volumes_ls() {
    print_header "Docker Volumes"
    docker volume ls | grep "${PROJECT_NAME}"
}

cmd_volumes_clean() {
    print_header "Cleaning unused volumes..."
    docker volume prune -f
    print_success "Unused volumes removed"
}

# Debugging commands
cmd_docker_ps() {
    print_header "All Containers (including stopped)"
    docker ps -a
}

cmd_docker_images() {
    print_header "Docker Images"
    docker images | grep "shopnow\|python\|php\|rabbitmq\|REPOSITORY"
}

cmd_docker_stats() {
    print_header "Real-time Container Statistics"
    docker stats
}

cmd_docker_prune() {
    print_header "Pruning unused Docker resources..."
    docker system prune -a --volumes -f
    print_success "Pruning complete"
}

# Utility commands
cmd_version() {
    print_header "Docker Version Information"
    docker --version
    docker compose --version
}

# Main command handler
main() {
    cd "$SCRIPT_DIR"
    
    case "${1:-help}" in
        # Build
        build) cmd_build "$2" ;;
        
        # Start
        up) cmd_up ;;
        up-detach) cmd_up_detach ;;
        up-prod) cmd_up_prod ;;
        up-with-php) cmd_up_with_php ;;
        
        # Stop
        down) cmd_down ;;
        stop) cmd_stop ;;
        restart) cmd_restart "$2" ;;
        
        # Clean
        clean) cmd_clean ;;
        clean-all) cmd_clean_all ;;
        
        # Status
        ps) cmd_ps ;;
        logs) cmd_logs "$2" ;;
        status) cmd_status ;;
        health) cmd_health ;;
        
        # Access
        shell) cmd_shell "$2" ;;
        exec) cmd_exec "$@" ;;
        test-connectivity) cmd_test_connectivity ;;
        test-api) cmd_test_api ;;
        open-urls) cmd_open_urls ;;
        
        # Development
        rebuild) cmd_rebuild "$2" ;;
        rebuild-all) cmd_rebuild_all ;;
        push-images) cmd_push_images ;;
        compose) shift; cmd_compose "$@" ;;
        
        # Network
        network-inspect) cmd_network_inspect ;;
        volumes-ls) cmd_volumes_ls ;;
        volumes-clean) cmd_volumes_clean ;;
        
        # Debugging
        docker-ps) cmd_docker_ps ;;
        docker-images) cmd_docker_images ;;
        docker-stats) cmd_docker_stats ;;
        docker-prune) cmd_docker_prune ;;
        
        # Utilities
        version) cmd_version ;;
        help) show_help ;;
        *)
            print_error "Unknown command: $1"
            echo ""
            show_help
            exit 1
            ;;
    esac
}

main "$@"
