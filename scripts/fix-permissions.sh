#!/bin/bash

# ==============================================================================
# VPS権限修正スクリプト (シンプル版)
# deployユーザーのDocker権限とファイル権限を修正
# ==============================================================================

# 色付きログ
RED='\033[0;31m'
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m'

log_info() { echo -e "${BLUE}[INFO]${NC} $1"; }
log_success() { echo -e "${GREEN}[SUCCESS]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }

PROJECT_DIR="/var/www/fuku-pochi"

log_info "VPS権限修正開始..."

# ==============================================================================
# 基本権限確認
# ==============================================================================

# 現在のユーザー確認
CURRENT_USER=$(whoami)
log_info "実行ユーザー: $CURRENT_USER"

# sudoアクセス確認
if ! sudo -n true 2>/dev/null; then
    log_error "sudo権限が必要です"
    log_info "ubuntu または root ユーザーで実行してください"
    exit 1
fi

# ==============================================================================
# deployユーザー確認と作成
# ==============================================================================

if ! id "deploy" >/dev/null 2>&1; then
    log_info "deployユーザーを作成中..."
    sudo useradd -m -s /bin/bash deploy
    log_success "deployユーザーを作成しました"
else
    log_info "deployユーザー確認済み"
fi

# ==============================================================================
# Docker権限設定
# ==============================================================================

log_info "Docker権限設定中..."

# dockerグループ存在確認
if ! getent group docker >/dev/null; then
    log_info "dockerグループを作成中..."
    sudo groupadd docker
fi

# deployユーザーをdockerグループに追加
sudo usermod -aG docker deploy

log_success "Docker権限設定完了"

# ==============================================================================
# プロジェクトファイル権限修正
# ==============================================================================

log_info "プロジェクトファイル権限修正中..."

if [ -d "${PROJECT_DIR}" ]; then
    # 所有権変更
    sudo chown -R deploy:deploy "${PROJECT_DIR}"
    
    # ディレクトリ権限
    sudo find "${PROJECT_DIR}" -type d -exec chmod 755 {} \;
    
    # ファイル権限
    sudo find "${PROJECT_DIR}" -type f -exec chmod 644 {} \;
    
    # スクリプト実行権限
    sudo chmod +x "${PROJECT_DIR}/scripts/"*.sh
    
    # 機密ファイルの権限
    if [ -f "${PROJECT_DIR}/.env.production" ]; then
        sudo chmod 600 "${PROJECT_DIR}/.env.production"
        log_success ".env.production 権限設定完了"
    fi
    
    log_success "プロジェクトファイル権限修正完了"
else
    log_error "プロジェクトディレクトリが見つかりません: ${PROJECT_DIR}"
    exit 1
fi

# ==============================================================================
# 権限テスト
# ==============================================================================

log_info "権限テスト実行中..."

# deployユーザーでのDockerテスト
if sudo -u deploy docker --version >/dev/null 2>&1; then
    log_success "deployユーザーのDocker実行権限 OK"
else
    log_error "deployユーザーのDocker実行権限に問題があります"
    log_info "システムを再起動するか、deployユーザーで新しいセッションを開始してください"
fi

# ==============================================================================
# 完了
# ==============================================================================

log_success "==================================="
log_success "権限修正完了！"
log_success "==================================="

echo ""
log_info "次のステップ:"
echo "1. deployユーザーに切り替え:"
echo "   sudo su - deploy"
echo ""
echo "2. プロジェクトディレクトリに移動:"
echo "   cd ${PROJECT_DIR}"
echo ""
echo "3. デプロイスクリプト実行:"
echo "   ./scripts/deploy.sh"
echo ""

log_info "権限確認用コマンド:"
echo "sudo -u deploy docker ps"
echo "sudo -u deploy ls -la ${PROJECT_DIR}"