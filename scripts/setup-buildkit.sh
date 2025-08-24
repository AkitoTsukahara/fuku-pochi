#!/bin/bash

# ==============================================================================
# Docker BuildKit セットアップスクリプト
# キャッシュ機能を有効化してビルド時間を短縮
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

log_info "Docker BuildKit セットアップ開始..."

# ==============================================================================
# Docker バージョンチェック
# ==============================================================================
log_info "Docker バージョン確認中..."
DOCKER_VERSION=$(docker --version | cut -d' ' -f3 | cut -d',' -f1)
log_info "Docker バージョン: ${DOCKER_VERSION}"

# ==============================================================================
# BuildKit 有効化設定
# ==============================================================================
log_info "BuildKit 設定中..."

# /etc/docker/daemon.json に BuildKit 設定追加
DAEMON_CONFIG="/etc/docker/daemon.json"
if [ ! -f "$DAEMON_CONFIG" ]; then
    log_info "daemon.json を新規作成中..."
    sudo mkdir -p /etc/docker
    echo '{}' | sudo tee "$DAEMON_CONFIG" > /dev/null
fi

# BuildKit設定を追加/更新
log_info "BuildKit設定を追加中..."
sudo python3 -c "
import json
import sys

config_file = '$DAEMON_CONFIG'
try:
    with open(config_file, 'r') as f:
        config = json.load(f)
except:
    config = {}

# BuildKit関連設定
config['features'] = config.get('features', {})
config['features']['buildkit'] = True
config['builder'] = config.get('builder', {})
config['builder']['gc'] = config['builder'].get('gc', {})
config['builder']['gc']['enabled'] = True
config['builder']['gc']['policy'] = [
    {'keepStorage': '512MB', 'all': True},
    {'keepStorage': '1GB', 'filters': ['type==source.local,type==exec.cachemount,type==source.git.checkout']},
    {'keepStorage': '2GB', 'filters': ['type!=source.local,type!=exec.cachemount,type!=source.git.checkout']}
]

with open(config_file, 'w') as f:
    json.dump(config, f, indent=2)
print('BuildKit configuration updated successfully')
" || {
    log_warning "Pythonによる設定更新に失敗。手動で設定中..."
    
    # 手動でBuildKit設定を追加
    sudo tee "$DAEMON_CONFIG" > /dev/null << 'EOF'
{
  "features": {
    "buildkit": true
  },
  "builder": {
    "gc": {
      "enabled": true,
      "policy": [
        {"keepStorage": "512MB", "all": true},
        {"keepStorage": "1GB", "filters": ["type==source.local,type==exec.cachemount,type==source.git.checkout"]},
        {"keepStorage": "2GB", "filters": ["type!=source.local,type!=exec.cachemount,type!=source.git.checkout"]}
      ]
    }
  }
}
EOF
}

log_success "daemon.json 設定完了"

# ==============================================================================
# 環境変数設定
# ==============================================================================
log_info "環境変数設定中..."

# deployユーザーの ~/.bashrc に BuildKit 環境変数を追加
BASHRC_FILE="/home/deploy/.bashrc"
if [ -f "$BASHRC_FILE" ]; then
    # 既存の設定があるかチェック
    if ! grep -q "DOCKER_BUILDKIT" "$BASHRC_FILE"; then
        echo "" >> "$BASHRC_FILE"
        echo "# Docker BuildKit Settings" >> "$BASHRC_FILE"
        echo "export DOCKER_BUILDKIT=1" >> "$BASHRC_FILE"
        echo "export COMPOSE_DOCKER_CLI_BUILD=1" >> "$BASHRC_FILE"
        log_success "deployユーザーのBuildKit環境変数設定完了"
    else
        log_info "BuildKit環境変数は既に設定済み"
    fi
fi

# 現在のセッションでも有効化
export DOCKER_BUILDKIT=1
export COMPOSE_DOCKER_CLI_BUILD=1

# ==============================================================================
# Docker サービス再起動
# ==============================================================================
log_info "Docker サービス再起動中..."
sudo systemctl restart docker

# サービス起動待機
sleep 5

# 再起動確認
if sudo systemctl is-active --quiet docker; then
    log_success "Docker サービス再起動完了"
else
    log_error "Docker サービスの再起動に失敗しました"
    exit 1
fi

# ==============================================================================
# BuildKit 動作確認
# ==============================================================================
log_info "BuildKit 動作確認中..."

# buildx コマンドが利用可能かチェック
if docker buildx version > /dev/null 2>&1; then
    log_success "docker buildx コマンド利用可能"
    docker buildx version
else
    log_warning "docker buildx コマンドが利用できません"
fi

# builder インスタンス確認
log_info "Builder インスタンス確認中..."
docker buildx ls

# BuildKit機能テスト
log_info "BuildKit機能テスト中..."
cat > /tmp/test.Dockerfile << 'EOF'
# syntax=docker/dockerfile:1
FROM alpine:latest
RUN --mount=type=cache,target=/tmp \
    echo "BuildKit cache mount test" > /tmp/test
EOF

if docker buildx build -f /tmp/test.Dockerfile -t buildkit-test /tmp > /dev/null 2>&1; then
    log_success "BuildKit機能テスト成功"
    docker rmi buildkit-test > /dev/null 2>&1 || true
else
    log_warning "BuildKit機能テストに失敗しましたが、基本機能は利用可能です"
fi

rm -f /tmp/test.Dockerfile

# ==============================================================================
# キャッシュ設定確認
# ==============================================================================
log_info "キャッシュ設定確認中..."

# キャッシュ使用状況表示
if docker buildx du > /dev/null 2>&1; then
    echo ""
    log_info "=== Build Cache Usage ==="
    docker buildx du
else
    log_info "キャッシュ情報はビルド実行後に表示されます"
fi

# ==============================================================================
# 完了
# ==============================================================================
echo ""
log_success "===================================="
log_success "BuildKit セットアップ完了！"
log_success "===================================="

echo ""
log_info "設定内容:"
echo "  - BuildKit機能: 有効"
echo "  - キャッシュGC: 有効"
echo "  - 環境変数: DOCKER_BUILDKIT=1"
echo "  - 環境変数: COMPOSE_DOCKER_CLI_BUILD=1"

echo ""
log_info "次のステップ:"
echo "1. 新しいSSHセッションを開始するか、以下を実行:"
echo "   source ~/.bashrc"
echo ""
echo "2. ビルド時間短縮確認:"
echo "   ./scripts/deploy.sh"
echo ""
echo "3. キャッシュ管理:"
echo "   ./scripts/manage-build-cache.sh analyze"

log_success "BuildKit が有効になりました。次回ビルドから高速化が適用されます！"