#!/bin/bash

# ==============================================================================
# Docker Build Cache Management Script
# Lightsail $5プラン用メモリ効率化
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

ACTION="${1:-analyze}"

# ==============================================================================
# Docker Cache Analysis
# ==============================================================================
analyze_cache() {
    log_info "Docker キャッシュ分析中..."
    
    echo ""
    log_info "=== Docker システム使用状況 ==="
    docker system df
    
    echo ""
    log_info "=== ビルドキャッシュ詳細 ==="
    docker buildx du || echo "BuildKit cache information not available"
    
    echo ""
    log_info "=== イメージサイズ詳細 ==="
    docker images --format "table {{.Repository}}\t{{.Tag}}\t{{.Size}}\t{{.CreatedAt}}" | head -20
    
    echo ""
    log_info "=== ダングリングイメージ ==="
    DANGLING_COUNT=$(docker images -f "dangling=true" -q | wc -l)
    echo "ダングリングイメージ数: $DANGLING_COUNT"
    
    echo ""
    log_info "=== 使用されていないボリューム ==="
    docker volume ls -f "dangling=true"
}

# ==============================================================================
# Gentle Cache Cleanup - キャッシュは保持、不要なもののみ削除
# ==============================================================================
gentle_cleanup() {
    log_info "Gentle クリーンアップ実行中..."
    
    # ダングリングイメージのみ削除（キャッシュレイヤーは保持）
    log_info "ダングリングイメージ削除中..."
    docker image prune -f --filter "dangling=true"
    
    # 使用されていないボリューム削除
    log_info "未使用ボリューム削除中..."
    docker volume prune -f
    
    # 使用されていないネットワーク削除
    log_info "未使用ネットワーク削除中..."
    docker network prune -f
    
    log_success "Gentle クリーンアップ完了"
}

# ==============================================================================
# Aggressive Cache Cleanup - より積極的な削除
# ==============================================================================
aggressive_cleanup() {
    log_warning "Aggressive クリーンアップ実行中..."
    log_warning "これにより次回ビルド時間が長くなる可能性があります"
    
    # 確認プロンプト
    read -p "本当に実行しますか？ (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        log_info "キャンセルされました"
        exit 0
    fi
    
    # システム全体をクリーンアップ
    docker system prune -a -f --volumes
    
    # BuildKitキャッシュもクリア
    docker buildx prune -a -f 2>/dev/null || true
    
    log_warning "Aggressive クリーンアップ完了"
    log_info "次回ビルドは初回ビルドと同等の時間がかかります"
}

# ==============================================================================
# Cache 最適化 - よく使用される base images を事前プル
# ==============================================================================
optimize_cache() {
    log_info "キャッシュ最適化実行中..."
    
    BASE_IMAGES=(
        "php:8.4-fpm-alpine"
        "node:20-alpine"
        "nginx:alpine"
        "mysql:8.4"
        "redis:7-alpine"
        "composer:2"
    )
    
    for image in "${BASE_IMAGES[@]}"; do
        log_info "プル中: $image"
        docker pull "$image" 2>/dev/null || log_warning "$image のプルに失敗"
    done
    
    log_success "キャッシュ最適化完了"
}

# ==============================================================================
# メイン処理
# ==============================================================================

case "$ACTION" in
    "analyze"|"a")
        analyze_cache
        ;;
    "gentle"|"g") 
        gentle_cleanup
        analyze_cache
        ;;
    "aggressive"|"ag")
        aggressive_cleanup
        analyze_cache
        ;;
    "optimize"|"o")
        optimize_cache
        analyze_cache
        ;;
    "full"|"f")
        log_info "フルメンテナンス実行中..."
        gentle_cleanup
        optimize_cache
        analyze_cache
        ;;
    *)
        echo "使用方法: $0 [analyze|gentle|aggressive|optimize|full]"
        echo ""
        echo "  analyze     - キャッシュ状況分析のみ"
        echo "  gentle      - 安全なクリーンアップ（キャッシュ保持）"
        echo "  aggressive  - 積極的クリーンアップ（キャッシュも削除）" 
        echo "  optimize    - ベースイメージ事前取得"
        echo "  full        - gentle + optimize"
        echo ""
        echo "例:"
        echo "  $0 analyze    # 現状分析"
        echo "  $0 gentle     # 日常的なクリーンアップ"
        echo "  $0 full       # 推奨メンテナンス"
        exit 1
        ;;
esac

log_success "完了！"