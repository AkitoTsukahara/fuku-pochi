#!/bin/bash

# ==============================================================================
# Nuclear Option - Docker Volume Physical Cleanup
# ボリューム物理削除による完全リセット
# ==============================================================================

set -euo pipefail

# 色付きログ
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log_info() { echo -e "${BLUE}[INFO]${NC} $1"; }
log_success() { echo -e "${GREEN}[SUCCESS]${NC} $1"; }
log_warning() { echo -e "${YELLOW}[WARNING]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }

log_warning "==================================="
log_warning "Nuclear Option - 完全リセット"
log_warning "==================================="

# ==============================================================================
# Step 1: すべて停止
# ==============================================================================
log_info "Step 1: Docker完全停止..."

# コンテナ全停止
docker stop $(docker ps -aq) 2>/dev/null || true
docker rm -f $(docker ps -aq) 2>/dev/null || true

# compose down
docker compose -f docker-compose.production.yml down -v 2>/dev/null || true

log_success "Docker停止完了"

# ==============================================================================
# Step 2: ボリューム物理削除
# ==============================================================================
log_info "Step 2: ボリューム物理削除中..."

# Dockerボリュームディレクトリの確認
DOCKER_ROOT=$(docker info 2>/dev/null | grep "Docker Root Dir" | awk '{print $NF}')
if [ -z "$DOCKER_ROOT" ]; then
    DOCKER_ROOT="/var/lib/docker"
fi

log_warning "Docker Root: ${DOCKER_ROOT}"

# fuku-pochiボリュームを物理削除
log_warning "物理削除実行中..."
sudo rm -rf ${DOCKER_ROOT}/volumes/fuku-pochi_* 2>/dev/null || true
sudo rm -rf ${DOCKER_ROOT}/volumes/webapp_* 2>/dev/null || true

# ボリューム一覧から削除
docker volume rm $(docker volume ls -q | grep -E "fuku-pochi|webapp") 2>/dev/null || true

log_success "物理削除完了"

# ==============================================================================
# Step 3: Docker daemon再起動
# ==============================================================================
log_info "Step 3: Docker daemon再起動中..."

sudo systemctl restart docker
sleep 5

# Docker動作確認
if docker ps >/dev/null 2>&1; then
    log_success "Docker daemon正常起動"
else
    log_error "Docker daemon起動失敗"
    exit 1
fi

# ==============================================================================
# Step 4: クリーンアップ
# ==============================================================================
log_info "Step 4: 完全クリーンアップ中..."

# すべてクリーン
docker system prune -a -f --volumes

log_success "クリーンアップ完了"

# ==============================================================================
# Step 5: ディレクトリ構造事前作成
# ==============================================================================
log_info "Step 5: ホストディレクトリ準備中..."

# ホスト側にディレクトリ構造を作成
PROJECT_DIR="/var/www/fuku-pochi"
sudo mkdir -p ${PROJECT_DIR}/storage/{logs,framework/{cache/data,sessions,views},app/public}
sudo mkdir -p ${PROJECT_DIR}/bootstrap/cache

# 権限設定 (www-data = UID 82 in Alpine)
sudo chown -R 82:82 ${PROJECT_DIR}/storage ${PROJECT_DIR}/bootstrap/cache
sudo chmod -R 775 ${PROJECT_DIR}/storage ${PROJECT_DIR}/bootstrap/cache

log_success "ディレクトリ準備完了"

# ==============================================================================
# Step 6: docker-compose.yml修正版作成
# ==============================================================================
log_info "Step 6: docker-compose修正版作成中..."

# バックアップ
cp docker-compose.production.yml docker-compose.production.yml.nuclear.backup

# 修正版作成（ボリュームをbindマウントに変更）
cat > docker-compose.production.fixed.yml << 'EOF'
services:
  backend:
    build:
      context: ./backend
      dockerfile: Dockerfile.production
    container_name: webapp_backend_prod
    expose:
      - "9000"
    volumes:
      # bindマウントに変更（ボリューム問題回避）
      - ./storage:/var/www/html/storage
      - ./bootstrap/cache:/var/www/html/bootstrap/cache
    env_file:
      - .env.production
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
      - DB_HOST=database
      - DB_PORT=3306
      - REDIS_HOST=redis
      - REDIS_PORT=6379
    depends_on:
      database:
        condition: service_healthy
      redis:
        condition: service_healthy
    networks:
      - webapp_network
    restart: unless-stopped
    mem_limit: 256M

  scheduler:
    build:
      context: ./backend
      dockerfile: Dockerfile.production
    container_name: webapp_scheduler_prod
    volumes:
      - ./storage:/var/www/html/storage
    env_file:
      - .env.production
    environment:
      - APP_ENV=production
      - DB_HOST=database
      - REDIS_HOST=redis
    depends_on:
      database:
        condition: service_healthy
      redis:
        condition: service_healthy
    networks:
      - webapp_network
    restart: unless-stopped
    command: ["php", "artisan", "schedule:work"]
    mem_limit: 64M

  queue_worker:
    build:
      context: ./backend
      dockerfile: Dockerfile.production
    container_name: webapp_queue_prod
    volumes:
      - ./storage:/var/www/html/storage
    env_file:
      - .env.production
    environment:
      - APP_ENV=production
      - DB_HOST=database
      - REDIS_HOST=redis
    depends_on:
      database:
        condition: service_healthy
      redis:
        condition: service_healthy
    networks:
      - webapp_network
    restart: unless-stopped
    command: ["php", "artisan", "queue:work", "--verbose", "--tries=3", "--timeout=90"]
    mem_limit: 128M

  # その他のサービスはボリューム使用のまま
  database:
    image: mysql:8.4
    container_name: webapp_database_prod
    expose:
      - "3306"
    environment:
      MYSQL_ROOT_PASSWORD: ${DB_ROOT_PASSWORD}
      MYSQL_DATABASE: ${DB_DATABASE:-fukupochi}
      MYSQL_USER: ${DB_USERNAME:-fukupochi_user}
      MYSQL_PASSWORD: ${DB_PASSWORD}
    volumes:
      - mysql_data_prod:/var/lib/mysql
    networks:
      - webapp_network
    restart: unless-stopped
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 30s
      timeout: 10s
      retries: 5
      start_period: 60s
    mem_limit: 384M

  redis:
    image: redis:7-alpine
    container_name: webapp_redis_prod
    expose:
      - "6379"
    volumes:
      - redis_data_prod:/data
    networks:
      - webapp_network
    restart: unless-stopped
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      interval: 30s
      timeout: 5s
      retries: 3
      start_period: 30s
    mem_limit: 128M

  frontend:
    build:
      context: ./frontend
      dockerfile: Dockerfile.production
    container_name: webapp_frontend_prod
    expose:
      - "3000"
    environment:
      - NODE_ENV=production
      - PUBLIC_API_BASE_URL=http://${SERVER_IP}/api
    depends_on:
      - backend
    networks:
      - webapp_network
    restart: unless-stopped
    mem_limit: 128M

  nginx:
    image: nginx:alpine
    container_name: webapp_nginx_prod
    ports:
      - "80:80"
    volumes:
      - ./nginx/nginx.ip.conf:/etc/nginx/nginx.conf:ro
      - ./backend/storage/app/public:/var/www/html/storage/app/public:ro
      - nginx_logs:/var/log/nginx
    depends_on:
      - backend
      - frontend
    networks:
      - webapp_network
    restart: unless-stopped
    mem_limit: 64M

volumes:
  mysql_data_prod:
    driver: local
  mysql_logs:
    driver: local
  redis_data_prod:
    driver: local
  redis_logs:
    driver: local
  nginx_logs:
    driver: local

networks:
  webapp_network:
    driver: bridge
EOF

log_success "修正版作成完了"

# ==============================================================================
# Step 7: 起動
# ==============================================================================
log_info "Step 7: 修正版で起動中..."

# 修正版で起動
docker compose -f docker-compose.production.fixed.yml up -d

# ==============================================================================
# Step 8: 確認
# ==============================================================================
log_info "Step 8: 起動確認中..."

sleep 10

docker ps

echo ""
log_success "===================================="
log_success "Nuclear Option 完了"
log_success "===================================="

echo ""
log_info "確認コマンド:"
echo "  docker ps"
echo "  docker compose -f docker-compose.production.fixed.yml ps"
echo "  curl http://localhost/health"

echo ""
log_warning "注意:"
echo "- app_storageとapp_bootstrap_cacheはbindマウントに変更されています"
echo "- 元に戻す場合: cp docker-compose.production.yml.nuclear.backup docker-compose.production.yml"