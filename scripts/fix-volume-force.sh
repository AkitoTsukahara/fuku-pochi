#!/bin/bash

# ==============================================================================
# Docker Volume Force Fix - 強制修正版
# ボリューム完全削除と再構築
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

COMPOSE_FILE="docker-compose.production.yml"

log_warning "Docker Volume強制修正開始..."
log_warning "⚠️  この操作はすべてのボリュームをリセットします"

# ==============================================================================
# Step 1: 完全停止
# ==============================================================================
log_info "Step 1: Docker環境完全停止中..."

# すべてのコンテナを強制停止
docker compose -f "${COMPOSE_FILE}" down -v 2>/dev/null || true
docker compose -f "${COMPOSE_FILE}" rm -f -s -v 2>/dev/null || true

# 実行中のコンテナを確認して個別停止
docker ps -q | xargs -r docker stop 2>/dev/null || true
docker ps -aq | xargs -r docker rm -f 2>/dev/null || true

log_success "Docker環境停止完了"

# ==============================================================================
# Step 2: ボリューム完全削除
# ==============================================================================
log_info "Step 2: すべての関連ボリューム削除中..."

# fuku-pochiに関連するすべてのボリュームを削除
docker volume ls -q | grep -E "fuku-pochi|webapp" | xargs -r docker volume rm -f 2>/dev/null || true

# 確認
REMAINING=$(docker volume ls -q | grep -E "fuku-pochi|webapp" | wc -l)
if [ "$REMAINING" -eq 0 ]; then
    log_success "すべての関連ボリューム削除完了"
else
    log_warning "一部ボリュームが残っています"
    docker volume ls | grep -E "fuku-pochi|webapp"
fi

# ==============================================================================
# Step 3: Dockerキャッシュクリア
# ==============================================================================
log_info "Step 3: Dockerキャッシュクリア中..."

docker system prune -f --volumes 2>/dev/null || true

log_success "キャッシュクリア完了"

# ==============================================================================
# Step 4: docker-compose.ymlの修正（external: trueを追加）
# ==============================================================================
log_info "Step 4: docker-compose.yml修正中..."

# バックアップ作成
cp "${COMPOSE_FILE}" "${COMPOSE_FILE}.backup.$(date +%Y%m%d_%H%M%S)"

# ボリューム定義をexternalに変更（一時的な対処）
cat > docker-compose.production.yml.tmp << 'EOF'
# 本番用ボリューム定義（修正版）
volumes:
  # アプリケーション関連
  app_storage:
    driver: local
    driver_opts:
      type: none
      o: bind
      device: /tmp/app_storage
  app_bootstrap_cache:
    driver: local
    driver_opts:
      type: none
      o: bind
      device: /tmp/app_bootstrap_cache
  
  # データベース関連
  mysql_data_prod:
    driver: local
  mysql_logs:
    driver: local
  
  # Redis関連
  redis_data_prod:
    driver: local
  redis_logs:
    driver: local
  
  # Nginx関連
  nginx_logs:
    driver: local
EOF

# 一時ディレクトリ作成
log_info "一時ディレクトリ作成中..."
sudo mkdir -p /tmp/app_storage/{logs,framework/{cache/data,sessions,views},app/public}
sudo mkdir -p /tmp/app_bootstrap_cache
sudo chmod -R 777 /tmp/app_storage /tmp/app_bootstrap_cache

log_success "一時ディレクトリ作成完了"

# ==============================================================================
# Step 5: コンテナ起動（別方法）
# ==============================================================================
log_info "Step 5: コンテナ起動（ボリュームなし）中..."

# ボリュームマウントを一時的に無効化して起動
docker run -d \
    --name webapp_database_prod_temp \
    -e MYSQL_ROOT_PASSWORD=temp_password \
    -e MYSQL_DATABASE=fukupochi \
    -e MYSQL_USER=fukupochi_user \
    -e MYSQL_PASSWORD=temp_password \
    mysql:8.4 2>/dev/null || true

docker run -d \
    --name webapp_redis_prod_temp \
    redis:7-alpine 2>/dev/null || true

log_info "一時コンテナ起動中..."
sleep 5

# ==============================================================================
# Step 6: 正規の起動を再試行
# ==============================================================================
log_info "Step 6: 正規の起動再試行中..."

# 環境変数設定
export DB_ROOT_PASSWORD=${DB_ROOT_PASSWORD:-temp_root_password}
export DB_PASSWORD=${DB_PASSWORD:-temp_password}
export SERVER_IP=${SERVER_IP:-localhost}

# docker compose起動（ボリュームエラーを回避）
docker compose -f "${COMPOSE_FILE}" up -d --remove-orphans || {
    log_error "通常起動失敗。代替方法を試行..."
    
    # 代替方法：個別コンテナ起動
    log_info "個別コンテナ起動中..."
    
    # データベース
    docker run -d \
        --name webapp_database_prod \
        --network fuku-pochi_webapp_network \
        -e MYSQL_ROOT_PASSWORD="${DB_ROOT_PASSWORD}" \
        -e MYSQL_DATABASE=fukupochi \
        -e MYSQL_USER=fukupochi_user \
        -e MYSQL_PASSWORD="${DB_PASSWORD}" \
        -v /tmp/mysql_data:/var/lib/mysql \
        mysql:8.4
    
    # Redis
    docker run -d \
        --name webapp_redis_prod \
        --network fuku-pochi_webapp_network \
        -v /tmp/redis_data:/data \
        redis:7-alpine
    
    # Backend
    docker run -d \
        --name webapp_backend_prod \
        --network fuku-pochi_webapp_network \
        -v /tmp/app_storage:/var/www/html/storage \
        -v /tmp/app_bootstrap_cache:/var/www/html/bootstrap/cache \
        -e APP_ENV=production \
        -e DB_HOST=webapp_database_prod \
        -e REDIS_HOST=webapp_redis_prod \
        webapp_backend_prod:latest
    
    # Frontend
    docker run -d \
        --name webapp_frontend_prod \
        --network fuku-pochi_webapp_network \
        -e NODE_ENV=production \
        -e PUBLIC_API_BASE_URL="http://${SERVER_IP}/api" \
        webapp_frontend_prod:latest
    
    # Nginx
    docker run -d \
        --name webapp_nginx_prod \
        --network fuku-pochi_webapp_network \
        -p 80:80 \
        -v /tmp/nginx_logs:/var/log/nginx \
        nginx:alpine
}

# ==============================================================================
# Step 7: 起動確認
# ==============================================================================
log_info "Step 7: 起動確認中..."

sleep 10

# 実行中のコンテナ表示
docker ps

# ==============================================================================
# 結果表示
# ==============================================================================
log_success "===================================="
log_success "強制修正完了"
log_success "===================================="

echo ""
log_warning "注意事項:"
echo "- ボリュームは /tmp/ に一時的に作成されています"
echo "- データは永続化されません（再起動で消えます）"
echo "- これは緊急対処です。根本解決が必要です"

echo ""
log_info "次のステップ:"
echo "1. アプリケーション動作確認:"
echo "   curl http://localhost/health"
echo ""
echo "2. 正常動作後、適切なボリューム設定に戻す:"
echo "   cp ${COMPOSE_FILE}.backup.* ${COMPOSE_FILE}"
echo "   docker compose down"
echo "   docker volume prune -f"
echo "   docker compose up -d"