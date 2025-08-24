#!/bin/bash

# ==============================================================================
# FukuPochi バックアップスクリプト
# データベース・ファイル・設定の定期バックアップ
# ==============================================================================

set -euo pipefail

# 色付きログ用の関数
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

log_info() {
    echo -e "${BLUE}[INFO]${NC} $(date '+%Y-%m-%d %H:%M:%S') $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $(date '+%Y-%m-%d %H:%M:%S') $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $(date '+%Y-%m-%d %H:%M:%S') $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $(date '+%Y-%m-%d %H:%M:%S') $1"
}

# ==============================================================================
# 設定変数
# ==============================================================================

PROJECT_NAME="fuku-pochi"
DEPLOY_DIR="/var/www/${PROJECT_NAME}"
COMPOSE_FILE="docker-compose.production.yml"
BACKUP_BASE_DIR="/var/backups/${PROJECT_NAME}"
RETENTION_DAYS=30
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# S3設定（オプション）
S3_BUCKET="${S3_BUCKET:-}"
S3_REGION="${S3_REGION:-ap-northeast-1}"

# 通知設定（オプション）
NOTIFICATION_EMAIL="${NOTIFICATION_EMAIL:-}"
WEBHOOK_URL="${WEBHOOK_URL:-}"

# ==============================================================================
# 環境変数読み込み
# ==============================================================================

if [ -f "${DEPLOY_DIR}/.env.production" ]; then
    source "${DEPLOY_DIR}/.env.production"
else
    log_error ".env.production ファイルが見つかりません"
    exit 1
fi

# ==============================================================================
# バックアップディレクトリ作成
# ==============================================================================

DAILY_BACKUP_DIR="${BACKUP_BASE_DIR}/daily/${TIMESTAMP}"
WEEKLY_BACKUP_DIR="${BACKUP_BASE_DIR}/weekly"
MONTHLY_BACKUP_DIR="${BACKUP_BASE_DIR}/monthly"

mkdir -p "${DAILY_BACKUP_DIR}"
mkdir -p "${WEEKLY_BACKUP_DIR}"
mkdir -p "${MONTHLY_BACKUP_DIR}"

log_info "バックアップディレクトリ作成: ${DAILY_BACKUP_DIR}"

# ==============================================================================
# データベースバックアップ
# ==============================================================================

log_info "データベースバックアップ開始..."

cd "${DEPLOY_DIR}"

# MySQLが起動しているかチェック
if ! docker compose -f "${COMPOSE_FILE}" ps database | grep -q "Up"; then
    log_error "データベースコンテナが起動していません"
    exit 1
fi

# データベースバックアップ実行
DB_BACKUP_FILE="${DAILY_BACKUP_DIR}/database_${TIMESTAMP}.sql"
DB_COMPRESSED_FILE="${DB_BACKUP_FILE}.gz"

docker compose -f "${COMPOSE_FILE}" exec -T database \
    mysqldump \
    --single-transaction \
    --routines \
    --triggers \
    --all-databases \
    -u root \
    -p"${DB_ROOT_PASSWORD}" > "${DB_BACKUP_FILE}"

# 圧縮
gzip "${DB_BACKUP_FILE}"

DB_SIZE=$(du -h "${DB_COMPRESSED_FILE}" | cut -f1)
log_success "データベースバックアップ完了: ${DB_COMPRESSED_FILE} (${DB_SIZE})"

# ==============================================================================
# アプリケーションファイルバックアップ
# ==============================================================================

log_info "アプリケーションファイルバックアップ開始..."

# ストレージディレクトリ
if [ -d "${DEPLOY_DIR}/storage" ]; then
    STORAGE_BACKUP_FILE="${DAILY_BACKUP_DIR}/storage_${TIMESTAMP}.tar.gz"
    tar -czf "${STORAGE_BACKUP_FILE}" -C "${DEPLOY_DIR}" storage
    STORAGE_SIZE=$(du -h "${STORAGE_BACKUP_FILE}" | cut -f1)
    log_success "ストレージバックアップ完了: ${STORAGE_BACKUP_FILE} (${STORAGE_SIZE})"
fi

# アップロードファイル（public/storage）
if [ -d "${DEPLOY_DIR}/backend/public/storage" ]; then
    UPLOADS_BACKUP_FILE="${DAILY_BACKUP_DIR}/uploads_${TIMESTAMP}.tar.gz"
    tar -czf "${UPLOADS_BACKUP_FILE}" -C "${DEPLOY_DIR}/backend/public" storage
    UPLOADS_SIZE=$(du -h "${UPLOADS_BACKUP_FILE}" | cut -f1)
    log_success "アップロードファイルバックアップ完了: ${UPLOADS_BACKUP_FILE} (${UPLOADS_SIZE})"
fi

# ==============================================================================
# 設定ファイルバックアップ
# ==============================================================================

log_info "設定ファイルバックアップ開始..."

CONFIG_BACKUP_FILE="${DAILY_BACKUP_DIR}/config_${TIMESTAMP}.tar.gz"

# 設定ファイル一覧
CONFIG_FILES=(
    ".env.production"
    "docker-compose.production.yml"
    "nginx/nginx.prod.conf"
    "mysql/my.cnf"
    "redis/redis.conf"
)

# 存在するファイルのみバックアップ
EXISTING_FILES=()
for file in "${CONFIG_FILES[@]}"; do
    if [ -f "${DEPLOY_DIR}/${file}" ]; then
        EXISTING_FILES+=("${file}")
    fi
done

if [ ${#EXISTING_FILES[@]} -gt 0 ]; then
    tar -czf "${CONFIG_BACKUP_FILE}" -C "${DEPLOY_DIR}" "${EXISTING_FILES[@]}"
    CONFIG_SIZE=$(du -h "${CONFIG_BACKUP_FILE}" | cut -f1)
    log_success "設定ファイルバックアップ完了: ${CONFIG_BACKUP_FILE} (${CONFIG_SIZE})"
fi

# ==============================================================================
# Dockerボリュームバックアップ
# ==============================================================================

log_info "Dockerボリュームバックアップ開始..."

VOLUMES_BACKUP_DIR="${DAILY_BACKUP_DIR}/volumes"
mkdir -p "${VOLUMES_BACKUP_DIR}"

# ボリューム一覧取得
VOLUMES=$(docker volume ls --format "table {{.Name}}" | grep "${PROJECT_NAME}" | tail -n +2)

for volume in $VOLUMES; do
    if [ -n "$volume" ]; then
        VOLUME_BACKUP_FILE="${VOLUMES_BACKUP_DIR}/${volume}_${TIMESTAMP}.tar.gz"
        
        # ボリュームをマウントしてバックアップ
        docker run --rm \
            -v "${volume}:/data:ro" \
            -v "${VOLUMES_BACKUP_DIR}:/backup" \
            alpine:latest \
            tar -czf "/backup/${volume}_${TIMESTAMP}.tar.gz" -C /data .
        
        if [ -f "${VOLUME_BACKUP_FILE}" ]; then
            VOLUME_SIZE=$(du -h "${VOLUME_BACKUP_FILE}" | cut -f1)
            log_success "ボリュームバックアップ完了: ${volume} (${VOLUME_SIZE})"
        fi
    fi
done

# ==============================================================================
# システム情報バックアップ
# ==============================================================================

log_info "システム情報バックアップ開始..."

SYSTEM_INFO_FILE="${DAILY_BACKUP_DIR}/system_info_${TIMESTAMP}.txt"

cat > "${SYSTEM_INFO_FILE}" << EOF
# FukuPochi System Information
# Generated: $(date)

## Git Information
Repository: $(git -C "${DEPLOY_DIR}" remote get-url origin)
Branch: $(git -C "${DEPLOY_DIR}" branch --show-current)
Commit: $(git -C "${DEPLOY_DIR}" rev-parse HEAD)
Commit Message: $(git -C "${DEPLOY_DIR}" log -1 --pretty=%B)

## Docker Information
Docker Version: $(docker --version)
Docker Compose Version: $(docker compose version)

## Running Containers
$(docker compose -f "${DEPLOY_DIR}/${COMPOSE_FILE}" ps)

## System Resources
$(free -h)
$(df -h)

## Package Versions
$(dpkg -l | grep -E "(nginx|mysql|redis|php)" || echo "No packages found")
EOF

log_success "システム情報バックアップ完了: ${SYSTEM_INFO_FILE}"

# ==============================================================================
# 週次・月次バックアップ
# ==============================================================================

# 週次バックアップ（日曜日）
if [ $(date +%u) -eq 7 ]; then
    log_info "週次バックアップ作成中..."
    WEEKLY_BACKUP_FILE="${WEEKLY_BACKUP_DIR}/weekly_backup_$(date +%Y_week_%W).tar.gz"
    tar -czf "${WEEKLY_BACKUP_FILE}" -C "${BACKUP_BASE_DIR}" "daily/${TIMESTAMP}"
    log_success "週次バックアップ完了: ${WEEKLY_BACKUP_FILE}"
fi

# 月次バックアップ（月末）
TOMORROW=$(date -d tomorrow +%d)
if [ "$TOMORROW" = "01" ]; then
    log_info "月次バックアップ作成中..."
    MONTHLY_BACKUP_FILE="${MONTHLY_BACKUP_DIR}/monthly_backup_$(date +%Y_%m).tar.gz"
    tar -czf "${MONTHLY_BACKUP_FILE}" -C "${BACKUP_BASE_DIR}" "daily/${TIMESTAMP}"
    log_success "月次バックアップ完了: ${MONTHLY_BACKUP_FILE}"
fi

# ==============================================================================
# S3アップロード（オプション）
# ==============================================================================

if [ -n "${S3_BUCKET}" ] && command -v aws &> /dev/null; then
    log_info "S3アップロード開始..."
    
    S3_KEY="backups/${PROJECT_NAME}/daily/${TIMESTAMP}/"
    
    aws s3 sync "${DAILY_BACKUP_DIR}" "s3://${S3_BUCKET}/${S3_KEY}" \
        --region "${S3_REGION}" \
        --storage-class STANDARD_IA
    
    log_success "S3アップロード完了: s3://${S3_BUCKET}/${S3_KEY}"
fi

# ==============================================================================
# 古いバックアップ削除
# ==============================================================================

log_info "古いバックアップ削除中..."

# 日次バックアップ削除（30日以上古い）
find "${BACKUP_BASE_DIR}/daily" -type d -mtime +${RETENTION_DAYS} -exec rm -rf {} + 2>/dev/null || true

# 週次バックアップ削除（12週以上古い）
find "${WEEKLY_BACKUP_DIR}" -name "weekly_backup_*.tar.gz" -mtime +84 -delete 2>/dev/null || true

# 月次バックアップ削除（12ヶ月以上古い）
find "${MONTHLY_BACKUP_DIR}" -name "monthly_backup_*.tar.gz" -mtime +365 -delete 2>/dev/null || true

# S3の古いバックアップ削除
if [ -n "${S3_BUCKET}" ] && command -v aws &> /dev/null; then
    CUTOFF_DATE=$(date -d "${RETENTION_DAYS} days ago" +%Y-%m-%d)
    aws s3api list-objects-v2 \
        --bucket "${S3_BUCKET}" \
        --prefix "backups/${PROJECT_NAME}/daily/" \
        --query "Contents[?LastModified<'${CUTOFF_DATE}T00:00:00.000Z'].Key" \
        --output text | \
    xargs -I {} aws s3 rm "s3://${S3_BUCKET}/{}" 2>/dev/null || true
fi

log_success "古いバックアップ削除完了"

# ==============================================================================
# バックアップサマリー作成
# ==============================================================================

BACKUP_SUMMARY_FILE="${DAILY_BACKUP_DIR}/backup_summary.txt"
TOTAL_SIZE=$(du -sh "${DAILY_BACKUP_DIR}" | cut -f1)

cat > "${BACKUP_SUMMARY_FILE}" << EOF
# FukuPochi Backup Summary
Generated: $(date)
Backup Directory: ${DAILY_BACKUP_DIR}
Total Size: ${TOTAL_SIZE}

## Files Created:
$(find "${DAILY_BACKUP_DIR}" -type f -exec ls -lh {} \; | awk '{print $5, $9}')

## Status: SUCCESS
EOF

# ==============================================================================
# 通知送信
# ==============================================================================

if [ -n "${NOTIFICATION_EMAIL}" ] && command -v mail &> /dev/null; then
    log_info "メール通知送信中..."
    
    subject="[${PROJECT_NAME}] Backup Completed - $(date +%Y-%m-%d)"
    mail -s "${subject}" "${NOTIFICATION_EMAIL}" < "${BACKUP_SUMMARY_FILE}"
    
    log_success "メール通知送信完了"
fi

if [ -n "${WEBHOOK_URL}" ]; then
    log_info "Webhook通知送信中..."
    
    curl -X POST "${WEBHOOK_URL}" \
        -H "Content-Type: application/json" \
        -d "{
            \"text\": \"✅ [${PROJECT_NAME}] Backup completed successfully\",
            \"timestamp\": \"$(date -Iseconds)\",
            \"size\": \"${TOTAL_SIZE}\",
            \"files\": $(find "${DAILY_BACKUP_DIR}" -type f | wc -l)
        }" || log_warning "Webhook通知の送信に失敗しました"
    
    log_success "Webhook通知送信完了"
fi

# ==============================================================================
# バックアップ完了
# ==============================================================================

log_success "==================================="
log_success "バックアップ完了！"
log_success "==================================="

echo ""
log_info "バックアップサマリー:"
echo "  - バックアップディレクトリ: ${DAILY_BACKUP_DIR}"
echo "  - 合計サイズ: ${TOTAL_SIZE}"
echo "  - ファイル数: $(find "${DAILY_BACKUP_DIR}" -type f | wc -l)"
echo "  - 実行時間: $(date)"

echo ""
log_info "主要ファイル:"
find "${DAILY_BACKUP_DIR}" -type f -name "*.gz" -exec ls -lh {} \; | awk '{print "  - " $9 " (" $5 ")"}'

echo ""
log_success "すべてのバックアップが正常に完了しました！"