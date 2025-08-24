#!/bin/bash

# ==============================================================================
# Base Images Build Script
# 事前ビルドされたベースイメージを作成・管理
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

# Docker BuildKit有効化
export DOCKER_BUILDKIT=1

# 設定
PROJECT_NAME="fuku-pochi"
REGISTRY_PREFIX="${PROJECT_NAME}"  # ローカル使用時
BASE_IMAGE_DIR="./docker/base-images"

ACTION="${1:-build}"

# ==============================================================================
# ベースイメージビルド
# ==============================================================================
build_base_images() {
    log_info "ベースイメージビルド開始..."
    
    # PHP Base Image
    log_info "PHP ベースイメージビルド中（10-15分かかります）..."
    docker build \
        --file "${BASE_IMAGE_DIR}/php-base.Dockerfile" \
        --tag "${REGISTRY_PREFIX}/php-base:8.4" \
        --tag "${REGISTRY_PREFIX}/php-base:latest" \
        --build-arg BUILDKIT_INLINE_CACHE=1 \
        --cache-from "${REGISTRY_PREFIX}/php-base:latest" \
        "${BASE_IMAGE_DIR}"
    
    if [ $? -eq 0 ]; then
        log_success "PHP ベースイメージビルド完了"
    else
        log_error "PHP ベースイメージビルド失敗"
        return 1
    fi
    
    # Node.js Base Image
    log_info "Node.js ベースイメージビルド中（3-5分かかります）..."
    docker build \
        --file "${BASE_IMAGE_DIR}/node-base.Dockerfile" \
        --tag "${REGISTRY_PREFIX}/node-base:20" \
        --tag "${REGISTRY_PREFIX}/node-base:latest" \
        --build-arg BUILDKIT_INLINE_CACHE=1 \
        --cache-from "${REGISTRY_PREFIX}/node-base:latest" \
        "${BASE_IMAGE_DIR}"
    
    if [ $? -eq 0 ]; then
        log_success "Node.js ベースイメージビルド完了"
    else
        log_error "Node.js ベースイメージビルド失敗"
        return 1
    fi
    
    log_success "全ベースイメージビルド完了"
}

# ==============================================================================
# ベースイメージテスト
# ==============================================================================
test_base_images() {
    log_info "ベースイメージテスト開始..."
    
    # PHP Base Test
    log_info "PHP ベースイメージテスト中..."
    if docker run --rm "${REGISTRY_PREFIX}/php-base:latest" php -v > /dev/null 2>&1; then
        log_success "PHP ベースイメージ: OK"
        
        # 拡張モジュール確認
        log_info "PHP 拡張モジュール確認中..."
        docker run --rm "${REGISTRY_PREFIX}/php-base:latest" php -m | grep -E "(pdo_mysql|gd|zip|opcache)" && \
            log_success "必要なPHP拡張確認完了" || \
            log_warning "一部PHP拡張が見つかりません"
    else
        log_error "PHP ベースイメージテスト失敗"
        return 1
    fi
    
    # Node.js Base Test  
    log_info "Node.js ベースイメージテスト中..."
    if docker run --rm "${REGISTRY_PREFIX}/node-base:latest" node --version > /dev/null 2>&1; then
        log_success "Node.js ベースイメージ: OK"
        
        # npm確認
        docker run --rm "${REGISTRY_PREFIX}/node-base:latest" npm --version > /dev/null 2>&1 && \
            log_success "npm確認完了" || \
            log_warning "npmが見つかりません"
    else
        log_error "Node.js ベースイメージテスト失敗"
        return 1
    fi
    
    log_success "全ベースイメージテスト完了"
}

# ==============================================================================
# イメージサイズ確認
# ==============================================================================
show_image_info() {
    log_info "ベースイメージ情報表示..."
    
    echo ""
    echo "=== ベースイメージ一覧 ==="
    docker images | grep -E "(${REGISTRY_PREFIX}/(php|node)-base|php:8.4|node:20)" | sort
    
    echo ""
    echo "=== サイズ比較 ==="
    PHP_BASE_SIZE=$(docker images --format "{{.Size}}" "${REGISTRY_PREFIX}/php-base:latest" 2>/dev/null || echo "N/A")
    PHP_OFFICIAL_SIZE=$(docker images --format "{{.Size}}" "php:8.4-fpm-alpine" 2>/dev/null || echo "N/A")
    NODE_BASE_SIZE=$(docker images --format "{{.Size}}" "${REGISTRY_PREFIX}/node-base:latest" 2>/dev/null || echo "N/A")
    NODE_OFFICIAL_SIZE=$(docker images --format "{{.Size}}" "node:20-alpine" 2>/dev/null || echo "N/A")
    
    echo "PHP:"
    echo "  - 公式イメージ (php:8.4-fpm-alpine): ${PHP_OFFICIAL_SIZE}"
    echo "  - カスタムベース (${REGISTRY_PREFIX}/php-base): ${PHP_BASE_SIZE}"
    echo ""
    echo "Node.js:"
    echo "  - 公式イメージ (node:20-alpine): ${NODE_OFFICIAL_SIZE}"  
    echo "  - カスタムベース (${REGISTRY_PREFIX}/node-base): ${NODE_BASE_SIZE}"
}

# ==============================================================================
# アプリケーションDockerfile更新
# ==============================================================================
update_dockerfiles() {
    log_info "アプリケーションDockerfileの更新ガイド表示..."
    
    echo ""
    log_info "=== Dockerfile更新方法 ==="
    echo ""
    echo "1. backend/Dockerfile.production の FROM文を変更:"
    echo "   変更前: FROM php:8.4-fmp-alpine AS builder"
    echo "   変更後: FROM ${REGISTRY_PREFIX}/php-base:latest AS builder"
    echo ""
    echo "2. frontend/Dockerfile.production の FROM文を変更:"
    echo "   変更前: FROM node:20-alpine AS builder"  
    echo "   変更後: FROM ${REGISTRY_PREFIX}/node-base:latest AS builder"
    echo ""
    echo "3. 不要になった処理をコメントアウト:"
    echo "   - apk add でのシステムパッケージインストール"
    echo "   - docker-php-ext-install での拡張インストール"
    echo "   - Node.js用ビルドツールインストール"
    echo ""
    log_warning "注意: ベースイメージが存在する環境でのみ動作します"
    log_info "本番環境にベースイメージをデプロイ後に更新してください"
}

# ==============================================================================
# クリーンアップ
# ==============================================================================
cleanup_base_images() {
    log_warning "ベースイメージクリーンアップ中..."
    
    read -p "ベースイメージを削除しますか？次回ビルド時間が長くなります (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        docker rmi "${REGISTRY_PREFIX}/php-base:latest" "${REGISTRY_PREFIX}/php-base:8.4" 2>/dev/null || true
        docker rmi "${REGISTRY_PREFIX}/node-base:latest" "${REGISTRY_PREFIX}/node-base:20" 2>/dev/null || true
        log_success "ベースイメージクリーンアップ完了"
    else
        log_info "キャンセルされました"
    fi
}

# ==============================================================================
# メイン処理
# ==============================================================================

case "$ACTION" in
    "build"|"b")
        build_base_images
        test_base_images
        show_image_info
        ;;
    "test"|"t")
        test_base_images
        ;;
    "info"|"i")
        show_image_info
        ;;
    "update-guide"|"u")
        update_dockerfiles
        ;;
    "cleanup"|"c")
        cleanup_base_images
        ;;
    "full"|"f")
        build_base_images
        test_base_images  
        show_image_info
        update_dockerfiles
        ;;
    *)
        echo "使用方法: $0 [build|test|info|update-guide|cleanup|full]"
        echo ""
        echo "  build        - ベースイメージをビルド"
        echo "  test         - ベースイメージをテスト"
        echo "  info         - イメージ情報表示"
        echo "  update-guide - Dockerfile更新ガイド表示"
        echo "  cleanup      - ベースイメージ削除"
        echo "  full         - build + test + info + update-guide"
        echo ""
        echo "初回実行推奨:"
        echo "  $0 full"
        echo ""
        echo "定期メンテナンス:"
        echo "  $0 build    # 月1回程度"
        exit 1
        ;;
esac

log_success "完了！"