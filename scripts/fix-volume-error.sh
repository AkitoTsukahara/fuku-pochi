#!/bin/bash

# ==============================================================================
# Docker Volume Error Emergency Fix
# ボリューム競合エラーの緊急修正
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

log_info "Docker Volume競合エラー修正開始..."

# ==============================================================================
# Step 1: サービス停止
# ==============================================================================
log_info "Step 1: 既存サービス停止中..."

docker compose -f "${COMPOSE_FILE}" down || true
docker compose -f "${COMPOSE_FILE}" rm -f || true

log_success "サービス停止完了"

# ==============================================================================
# Step 2: 問題のあるボリューム削除
# ==============================================================================
log_info "Step 2: 競合ボリューム削除中..."

# app_storageボリュームを特定して削除
log_warning "app_storageボリュームを削除します..."
docker volume rm fuku-pochi_app_storage -f 2>/dev/null || true
docker volume rm fuku-pochi_app_bootstrap_cache -f 2>/dev/null || true

log_success "競合ボリューム削除完了"

# ==============================================================================
# Step 3: 全ボリューム確認
# ==============================================================================
log_info "Step 3: ボリューム状態確認中..."

# 残っているボリューム一覧
echo "現在のボリューム:"
docker volume ls | grep -E "fuku-pochi|webapp" || echo "関連ボリュームなし"

# ==============================================================================
# Step 4: ボリューム再作成
# ==============================================================================
log_info "Step 4: ボリューム再作成中..."

# ボリュームを明示的に作成
docker volume create fuku-pochi_app_storage
docker volume create fuku-pochi_app_bootstrap_cache

log_success "ボリューム再作成完了"

# ==============================================================================
# Step 5: コンテナ起動（再試行）
# ==============================================================================
log_info "Step 5: コンテナ起動再試行中..."

# 環境変数確認
if [ ! -f ".env.production" ]; then
    log_error ".env.production が見つかりません"
    exit 1
fi

# コンテナ起動
docker compose -f "${COMPOSE_FILE}" up -d

# ==============================================================================
# Step 6: 起動確認
# ==============================================================================
log_info "Step 6: 起動状態確認中..."

sleep 10

# サービス状態表示
docker compose -f "${COMPOSE_FILE}" ps

# 起動確認
SERVICES=("nginx" "backend" "frontend" "database" "redis")
FAILED_SERVICES=()

for service in "${SERVICES[@]}"; do
    if docker compose -f "${COMPOSE_FILE}" ps "${service}" | grep -q "Up"; then
        log_success "${service}: 起動成功"
    else
        log_error "${service}: 起動失敗"
        FAILED_SERVICES+=("${service}")
    fi
done

# ==============================================================================
# 結果表示
# ==============================================================================
if [ ${#FAILED_SERVICES[@]} -eq 0 ]; then
    log_success "===================================="
    log_success "ボリュームエラー修正完了！"
    log_success "===================================="
    echo ""
    log_info "全サービス正常起動しました"
else
    log_warning "以下のサービスが起動に失敗しました: ${FAILED_SERVICES[*]}"
    echo ""
    log_info "ログ確認:"
    for service in "${FAILED_SERVICES[@]}"; do
        echo "docker compose -f ${COMPOSE_FILE} logs --tail=50 ${service}"
    done
fi

echo ""
log_info "アプリケーション確認:"
echo "curl http://localhost/health"