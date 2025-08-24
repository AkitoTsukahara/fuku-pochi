#!/bin/bash

# ==============================================================================
# Build Optimization Implementation Guide
# Lightsail $5プランでのビルド時間最適化の段階的導入
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

ACTION="${1:-guide}"

# ==============================================================================
# 最適化段階の説明
# ==============================================================================
show_optimization_stages() {
    echo ""
    log_info "=== Build Optimization Stages ==="
    echo ""
    echo "📊 現在のビルド時間: 25-40分 (1vCPU, 1GB RAM)"
    echo ""
    echo "🏃‍♂️ Stage 1: Layer Caching (60-70%短縮)"
    echo "   - ビルド時間: 15-25分"
    echo "   - 実装済み: ✅ Dockerfile.production"
    echo "   - 必要作業: BuildKit有効化のみ"
    echo ""
    echo "🚀 Stage 2: Pre-built Base Images (80%短縮)"
    echo "   - ビルド時間: 8-15分"
    echo "   - 実装済み: ✅ docker/base-images/"
    echo "   - 必要作業: ベースイメージビルド + Dockerfile切り替え"
    echo ""
    echo "⚡ Stage 3: Ultra Multi-Stage (85%短縮)"
    echo "   - ビルド時間: 5-12分"
    echo "   - 実装済み: ✅ Dockerfile.production.ultra"
    echo "   - 必要作業: 高度な設定 + 依存関係最適化"
    echo ""
    log_warning "注意: 段階的に導入してください。いきなりUltraは推奨しません。"
}

# ==============================================================================
# Stage 1: Layer Caching Implementation
# ==============================================================================
implement_stage1() {
    log_info "Stage 1: Layer Caching 実装開始..."
    
    # BuildKit設定
    log_info "1. BuildKit有効化..."
    if [ -f "scripts/setup-buildkit.sh" ]; then
        chmod +x scripts/setup-buildkit.sh
        log_info "BuildKit設定スクリプトを実行してください:"
        echo "  sudo ./scripts/setup-buildkit.sh"
    else
        log_error "BuildKit設定スクリプトが見つかりません"
        return 1
    fi
    
    # 現在のDockerfileが最適化版か確認
    if grep -q "Layer Cache 最適化版" backend/Dockerfile.production 2>/dev/null; then
        log_success "Backend Dockerfile: 最適化済み"
    else
        log_warning "Backend Dockerfile: 最適化が必要"
    fi
    
    if grep -q "Layer Cache 最適化版" frontend/Dockerfile.production 2>/dev/null; then
        log_success "Frontend Dockerfile: 最適化済み"
    else
        log_warning "Frontend Dockerfile: 最適化が必要"
    fi
    
    # デプロイテスト
    log_info "テストデプロイを実行してください:"
    echo "  ./scripts/deploy.sh"
    
    log_success "Stage 1 実装ガイド完了"
    echo ""
    log_info "期待効果:"
    echo "  - 初回: 25-40分 (変化なし)"
    echo "  - 2回目以降: 10-15分 (60-70%短縮)"
}

# ==============================================================================
# Stage 2: Pre-built Base Images Implementation
# ==============================================================================
implement_stage2() {
    log_info "Stage 2: Pre-built Base Images 実装開始..."
    
    log_warning "⚠️  この段階は上級者向けです。Stage 1で効果を確認後に実施してください。"
    
    # ベースイメージビルド
    log_info "1. ベースイメージビルド (15-20分かかります)..."
    if [ -f "scripts/build-base-images.sh" ]; then
        log_info "以下のコマンドでベースイメージをビルドしてください:"
        echo "  ./scripts/build-base-images.sh build"
        echo ""
        log_info "ビルド完了後、テスト実行:"
        echo "  ./scripts/build-base-images.sh test"
    else
        log_error "ベースイメージビルドスクリプトが見つかりません"
        return 1
    fi
    
    # Dockerfile切り替え
    log_info "2. Dockerfileの切り替え..."
    echo "以下のファイルを使用してください:"
    echo "  - backend/Dockerfile.production.optimized"
    echo "  - frontend/Dockerfile.production.optimized"
    echo ""
    echo "切り替え手順:"
    echo "  cp backend/Dockerfile.production backend/Dockerfile.production.backup"
    echo "  cp backend/Dockerfile.production.optimized backend/Dockerfile.production"
    echo "  cp frontend/Dockerfile.production frontend/Dockerfile.production.backup"
    echo "  cp frontend/Dockerfile.production.optimized frontend/Dockerfile.production"
    
    log_success "Stage 2 実装ガイド完了"
    echo ""
    log_info "期待効果:"
    echo "  - 初回: 15-20分 (ベースイメージビルド)"
    echo "  - 2回目以降: 3-8分 (80%短縮)"
}

# ==============================================================================
# Stage 3: Ultra Multi-Stage Implementation
# ==============================================================================
implement_stage3() {
    log_info "Stage 3: Ultra Multi-Stage 実装開始..."
    
    log_error "⚠️  これは実験的な最適化です。本番環境での使用は慎重に検討してください。"
    
    log_info "Ultra版の特徴:"
    echo "  - BuildKit cache mounts"
    echo "  - 並列ビルドステージ"
    echo "  - 最小限のproduction image"
    echo "  - 高度なキャッシュ戦略"
    echo ""
    
    log_info "利用可能なファイル:"
    echo "  - backend/Dockerfile.production.ultra"
    echo "  - frontend/Dockerfile.production.ultra"
    echo ""
    
    log_warning "前提条件:"
    echo "  - Docker BuildKit 有効"
    echo "  - 十分なテスト環境"
    echo "  - Stage 1, 2での経験"
    echo ""
    
    log_info "テスト手順:"
    echo "1. 開発環境でテスト:"
    echo "   docker build -f backend/Dockerfile.production.ultra -t test-backend ."
    echo "   docker build -f frontend/Dockerfile.production.ultra -t test-frontend ."
    echo ""
    echo "2. 動作確認:"
    echo "   docker run --rm test-backend php --version"
    echo "   docker run --rm test-frontend node --version"
    echo ""
    echo "3. 問題なければ本番適用"
    
    log_success "Stage 3 実装ガイド完了"
    echo ""
    log_info "期待効果:"
    echo "  - 初回: 8-12分"
    echo "  - 2回目以降: 3-5分 (85%短縮)"
}

# ==============================================================================
# パフォーマンス監視
# ==============================================================================
monitor_performance() {
    log_info "ビルドパフォーマンス監視方法..."
    
    echo ""
    log_info "=== ビルド時間計測 ==="
    echo "以下をデプロイスクリプトに追加:"
    echo 'BUILD_START=$(date +%s)'
    echo '# ... ビルド処理 ...'
    echo 'BUILD_END=$(date +%s)'
    echo 'BUILD_TIME=$((BUILD_END - BUILD_START))'
    echo 'echo "ビルド時間: ${BUILD_TIME}秒"'
    echo ""
    
    log_info "=== キャッシュ使用状況確認 ==="
    echo "docker system df"
    echo "docker buildx du"
    echo ""
    
    log_info "=== イメージサイズ確認 ==="
    echo "docker images | head -10"
    echo ""
    
    log_info "=== ログ確認 ==="
    echo "ビルドログを確認してキャッシュヒット率を確認してください"
    echo '例: "CACHED" の出現回数が多いほど最適化されています'
}

# ==============================================================================
# トラブルシューティング
# ==============================================================================
troubleshoot() {
    log_info "よくある問題と対処法..."
    
    echo ""
    log_warning "=== 問題1: BuildKitが効かない ==="
    echo "症状: ビルド時間が変わらない"
    echo "対処: export DOCKER_BUILDKIT=1 を確認"
    echo "確認: docker version | grep -i buildkit"
    echo ""
    
    log_warning "=== 問題2: メモリ不足 ==="
    echo "症状: OOMで異常終了"
    echo "対処: 順次ビルドを確認"
    echo "対処: docker system prune でクリーンアップ"
    echo ""
    
    log_warning "=== 問題3: キャッシュが効かない ==="
    echo "症状: 毎回フルビルド"
    echo "対処: .dockerignore を確認"
    echo "対処: COPY命令の順序を確認"
    echo ""
    
    log_warning "=== 問題4: ベースイメージが見つからない ==="
    echo "症状: FROM で ERROR"
    echo "対処: ./scripts/build-base-images.sh build を実行"
    echo "対処: docker images でイメージ存在確認"
    echo ""
    
    log_info "詳細なヘルプ:"
    echo "  ./scripts/manage-build-cache.sh analyze"
}

# ==============================================================================
# 推奨実装パス
# ==============================================================================
show_recommended_path() {
    log_info "=== 推奨実装パス ==="
    echo ""
    echo "🔰 初心者・安全重視:"
    echo "  1. Stage 1 (Layer Caching) のみ実装"
    echo "  2. 数回デプロイして効果を確認"
    echo "  3. 問題なければ運用継続"
    echo ""
    echo "🚀 中級者・さらなる高速化:"
    echo "  1. Stage 1 で安定運用"
    echo "  2. Stage 2 (Pre-built Base) 導入"
    echo "  3. ベースイメージ管理体制構築"
    echo ""
    echo "⚡ 上級者・最大最適化:"
    echo "  1. Stage 1, 2 で経験蓄積"
    echo "  2. Stage 3 (Ultra) を開発環境でテスト"
    echo "  3. 段階的に本番適用"
    echo ""
    echo "⏰ 時間がない場合:"
    echo "  1. ./scripts/setup-buildkit.sh を実行"
    echo "  2. 現在のDockerfileでビルド時間確認"
    echo "  3. 効果があれば継続、なければ相談"
}

# ==============================================================================
# メイン処理
# ==============================================================================

case "$ACTION" in
    "guide"|"g")
        show_optimization_stages
        show_recommended_path
        ;;
    "stage1"|"1")
        implement_stage1
        ;;
    "stage2"|"2")
        implement_stage2
        ;;
    "stage3"|"3")
        implement_stage3
        ;;
    "monitor"|"m")
        monitor_performance
        ;;
    "troubleshoot"|"t")
        troubleshoot
        ;;
    "all"|"a")
        show_optimization_stages
        show_recommended_path
        echo ""
        implement_stage1
        echo ""
        implement_stage2
        echo ""
        implement_stage3
        ;;
    *)
        echo "Usage: $0 [guide|stage1|stage2|stage3|monitor|troubleshoot|all]"
        echo ""
        echo "  guide        - 最適化段階とガイド表示"
        echo "  stage1       - Stage 1 (Layer Caching) 実装"
        echo "  stage2       - Stage 2 (Pre-built Base) 実装"
        echo "  stage3       - Stage 3 (Ultra Multi-Stage) 実装"
        echo "  monitor      - パフォーマンス監視方法"
        echo "  troubleshoot - トラブルシューティング"
        echo "  all          - 全情報表示"
        echo ""
        echo "推奨初回実行:"
        echo "  $0 guide"
        echo "  $0 stage1"
        exit 1
        ;;
esac

echo ""
log_success "最適化ガイド完了！"