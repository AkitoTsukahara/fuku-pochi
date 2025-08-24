#!/bin/bash

# ==============================================================================
# Lightsail 自動デプロイスクリプト
# 本番環境への継続的デプロイメント
# ==============================================================================

set -euo pipefail

# Docker BuildKit有効化（ビルドキャッシュ利用）
export DOCKER_BUILDKIT=1
export COMPOSE_DOCKER_CLI_BUILD=1

# 色付きログ用の関数
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# ==============================================================================
# 設定変数
# ==============================================================================

log_info "デプロイ前チェック中..."

# 現在のディレクトリから自動判定（実行場所がプロジェクトディレクトリ）
DEPLOY_DIR="$(pwd)"
PROJECT_NAME="$(basename "${DEPLOY_DIR}")"

# パラメータ設定
COMPOSE_FILE="docker-compose.production.yml"
BACKUP_DIR="/var/backups/${PROJECT_NAME}"
BRANCH="${1:-main}"
SKIP_BACKUP="${2:-false}"

log_info "プロジェクトディレクトリ: ${DEPLOY_DIR}"
log_info "プロジェクト名: ${PROJECT_NAME}"

# ==============================================================================
# 前提条件チェック
# ==============================================================================

# 必要なファイルチェック
if [ ! -f "${DEPLOY_DIR}/${COMPOSE_FILE}" ]; then
    log_error "${COMPOSE_FILE} ファイルが存在しません"
    log_error "プロジェクトのルートディレクトリで実行してください"
    exit 1
fi

# 環境変数ファイルチェックと読み込み
if [ ! -f "${DEPLOY_DIR}/.env.production" ]; then
    log_error ".env.production ファイルが存在しません"
    log_info "テンプレートをコピーして設定してください:"
    log_info "cp .env.production.example .env.production"
    exit 1
fi

# 環境変数を読み込み（セキュリティ対応）
source "${DEPLOY_DIR}/.env.production"
export $(grep -v '^#' "${DEPLOY_DIR}/.env.production" | grep '=' | cut -d= -f1)

# Dockerチェック
if ! command -v docker &> /dev/null; then
    log_error "Dockerがインストールされていません"
    exit 1
fi

# Docker Composeチェック
if ! docker compose --version &> /dev/null; then
    log_error "Docker Composeがインストールされていません"
    exit 1
fi

# ==============================================================================
# バックアップ（オプション）
# ==============================================================================

if [ "${SKIP_BACKUP}" != "true" ]; then
    log_info "デプロイ前バックアップ実行中..."
    
    # バックアップディレクトリ作成
    mkdir -p "${BACKUP_DIR}"
    
    # データベースバックアップ（セキュリティ対応）
    if docker compose -f "${DEPLOY_DIR}/${COMPOSE_FILE}" ps database | grep -q "Up"; then
        BACKUP_FILE="${BACKUP_DIR}/db-backup-$(date +%Y%m%d_%H%M%S).sql"
        docker compose -f "${DEPLOY_DIR}/${COMPOSE_FILE}" exec -T database \
            mysqldump -u root -p"${DB_ROOT_PASSWORD}" "${DB_DATABASE}" > "${BACKUP_FILE}"
        log_success "データベースバックアップ完了: ${BACKUP_FILE}"
    else
        log_warning "データベースコンテナが起動していないため、バックアップをスキップします"
    fi
    
    # アップロードファイルバックアップ
    if [ -d "${DEPLOY_DIR}/storage/app/public" ]; then
        tar -czf "${BACKUP_DIR}/storage-backup-$(date +%Y%m%d_%H%M%S).tar.gz" \
            -C "${DEPLOY_DIR}" storage/app/public
        log_success "ストレージバックアップ完了"
    fi
else
    log_warning "バックアップをスキップしました"
fi

# ==============================================================================
# ソースコード更新
# ==============================================================================

log_info "ソースコード更新中 (ブランチ: ${BRANCH})..."

cd "${DEPLOY_DIR}"

# Gitの設定（安全対策）
git config --global --add safe.directory "${DEPLOY_DIR}"

# 現在のコミットハッシュを記録（ロールバック用）
CURRENT_COMMIT=$(git rev-parse HEAD)
log_info "現在のコミット: ${CURRENT_COMMIT}"

# リモートから最新を取得
git fetch origin

# ローカル変更をスタッシュ
if ! git diff-index --quiet HEAD --; then
    log_warning "ローカル変更を検出しました。スタッシュします..."
    git stash push -m "Auto-stash before deploy $(date)"
fi

# ブランチをチェックアウト
git checkout "${BRANCH}"
git pull origin "${BRANCH}"

NEW_COMMIT=$(git rev-parse HEAD)
log_info "新しいコミット: ${NEW_COMMIT}"

if [ "${CURRENT_COMMIT}" = "${NEW_COMMIT}" ]; then
    log_info "変更がありません。デプロイを続行します..."
else
    log_success "コード更新完了"
fi

# ==============================================================================
# 環境変数の整合性チェック
# ==============================================================================

log_info "環境変数整合性チェック中..."

# .env.production.example と .env.production の差分確認
if [ -f ".env.production.example" ]; then
    # 新しい環境変数があるかチェック
    NEW_VARS=$(grep -v '^#' .env.production.example | grep '=' | cut -d'=' -f1 | sort)
    CURRENT_VARS=$(grep -v '^#' .env.production | grep '=' | cut -d'=' -f1 | sort)
    
    MISSING_VARS=$(comm -23 <(echo "$NEW_VARS") <(echo "$CURRENT_VARS"))
    
    if [ -n "$MISSING_VARS" ]; then
        log_warning "以下の環境変数が .env.production に不足しています:"
        echo "$MISSING_VARS"
        log_warning "続行する前に設定を確認してください"
    fi
fi

# ==============================================================================
# Dockerイメージビルド
# ==============================================================================

log_info "Dockerイメージビルド中..."

# 不要なイメージのみクリーンアップ（キャッシュは保持）
docker image prune -f --filter "dangling=true"

# Lightsail $5プラン対応：順次ビルド（メモリ制約考慮）+ 積極的キャッシュ利用
log_info "バックエンドビルド中（キャッシュ利用）..."
docker compose -f "${COMPOSE_FILE}" build --build-arg BUILDKIT_INLINE_CACHE=1 backend

log_info "フロントエンドビルド中（キャッシュ利用）..."
docker compose -f "${COMPOSE_FILE}" build --build-arg BUILDKIT_INLINE_CACHE=1 frontend

log_info "その他サービスビルド中..."
docker compose -f "${COMPOSE_FILE}" build nginx scheduler queue_worker

log_success "Dockerイメージビルド完了"

# ==============================================================================
# デプロイ実行
# ==============================================================================

log_info "アプリケーションデプロイ中..."

# メンテナンスモード有効化（Laravelアプリケーションが起動している場合）
if docker compose -f "${COMPOSE_FILE}" ps backend | grep -q "Up"; then
    log_info "メンテナンスモード有効化中..."
    docker compose -f "${COMPOSE_FILE}" exec -T backend php artisan down || true
fi

# サービス停止
log_info "既存サービス停止中..."
docker compose -f "${COMPOSE_FILE}" down

# 新しいサービス起動
log_info "新しいサービス起動中..."
docker compose -f "${COMPOSE_FILE}" up -d

# サービス起動待機
log_info "サービス起動待機中..."
sleep 30

# ==============================================================================
# ヘルスチェック
# ==============================================================================

log_info "ヘルスチェック実行中..."

# 各サービスの状態確認
SERVICES=("nginx" "backend" "frontend" "database" "redis")
FAILED_SERVICES=()

for service in "${SERVICES[@]}"; do
    if docker compose -f "${COMPOSE_FILE}" ps "${service}" | grep -q "Up"; then
        log_success "${service}: 起動中"
    else
        log_error "${service}: 停止中"
        FAILED_SERVICES+=("${service}")
    fi
done

# 失敗したサービスがある場合
if [ ${#FAILED_SERVICES[@]} -gt 0 ]; then
    log_error "以下のサービスが起動に失敗しました: ${FAILED_SERVICES[*]}"
    
    # ログ出力
    for service in "${FAILED_SERVICES[@]}"; do
        log_info "${service} のログ:"
        docker compose -f "${COMPOSE_FILE}" logs --tail=50 "${service}"
    done
    
    # ロールバック確認
    read -p "ロールバックしますか？ (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        log_info "ロールバック実行中..."
        git checkout "${CURRENT_COMMIT}"
        docker compose -f "${COMPOSE_FILE}" down
        docker compose -f "${COMPOSE_FILE}" up -d
        log_success "ロールバック完了"
    fi
    
    exit 1
fi

# ==============================================================================
# Laravel最適化コマンド実行
# ==============================================================================

log_info "Laravel最適化コマンド実行中..."

# マイグレーション実行
log_info "データベースマイグレーション実行中..."
docker compose -f "${COMPOSE_FILE}" exec -T backend php artisan migrate --force

# キャッシュクリア・再構築
log_info "キャッシュ最適化中..."
docker compose -f "${COMPOSE_FILE}" exec -T backend php artisan config:cache
docker compose -f "${COMPOSE_FILE}" exec -T backend php artisan route:cache
docker compose -f "${COMPOSE_FILE}" exec -T backend php artisan view:cache
docker compose -f "${COMPOSE_FILE}" exec -T backend php artisan event:cache

# ストレージリンク作成
docker compose -f "${COMPOSE_FILE}" exec -T backend php artisan storage:link || true

# メンテナンスモード解除
log_info "メンテナンスモード解除中..."
docker compose -f "${COMPOSE_FILE}" exec -T backend php artisan up

log_success "Laravel最適化完了"

# ==============================================================================
# 最終ヘルスチェック
# ==============================================================================

log_info "最終ヘルスチェック実行中..."

# HTTP接続テスト（環境変数対応）
SERVER_URL="http://${SERVER_IP:-localhost}"
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" "${SERVER_URL}/health" || echo "000")
if [ "${HTTP_STATUS}" = "200" ]; then
    log_success "HTTP ヘルスチェック: OK (${HTTP_STATUS})"
else
    log_error "HTTP ヘルスチェック: NG (${HTTP_STATUS})"
fi

# API接続テスト（環境変数対応）
API_STATUS=$(curl -s -o /dev/null -w "%{http_code}" "${SERVER_URL}/api/health" || echo "000")
if [ "${API_STATUS}" = "200" ]; then
    log_success "API ヘルスチェック: OK (${API_STATUS})"
else
    log_error "API ヘルスチェック: NG (${API_STATUS})"
fi

# ==============================================================================
# クリーンアップ
# ==============================================================================

log_info "クリーンアップ実行中..."

# 古いDockerイメージ削除
docker image prune -f

# ログローテーション
find /var/log -name "*.log" -size +100M -delete

log_success "クリーンアップ完了"

# ==============================================================================
# デプロイ完了
# ==============================================================================

log_success "==================================="
log_success "デプロイ完了！"
log_success "==================================="

echo ""
log_info "デプロイサマリー:"
echo "  - ブランチ: ${BRANCH}"
echo "  - 旧コミット: ${CURRENT_COMMIT}"
echo "  - 新コミット: ${NEW_COMMIT}"
echo "  - デプロイ時刻: $(date)"

echo ""
log_info "アプリケーション情報:"
echo "  - Frontend: ${SERVER_URL}"
echo "  - API: ${SERVER_URL}/api"
echo "  - Health Check: ${SERVER_URL}/health"

echo ""
log_info "ログ確認コマンド:"
echo "  - 全サービス: docker compose -f ${COMPOSE_FILE} logs -f"
echo "  - Backend: docker compose -f ${COMPOSE_FILE} logs -f backend"
echo "  - Frontend: docker compose -f ${COMPOSE_FILE} logs -f frontend"

echo ""
log_success "デプロイが正常に完了しました！"