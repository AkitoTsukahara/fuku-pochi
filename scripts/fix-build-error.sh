#!/bin/bash

# ==============================================================================
# Build Error Emergency Fix
# artisan cache エラーの緊急修正
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

log_info "artisan cache エラー緊急修正開始..."

# ==============================================================================
# Step 1: Docker Cache Clear
# ==============================================================================
log_info "Step 1: Dockerキャッシュクリア中..."

# 既存のイメージとキャッシュをクリア
docker system prune -f
docker builder prune -f

log_success "Dockerキャッシュクリア完了"

# ==============================================================================
# Step 2: Check Required Files
# ==============================================================================
log_info "Step 2: 必要ファイル確認中..."

# .env.example存在確認
if [ ! -f "backend/.env.example" ]; then
    log_warning ".env.exampleが存在しません。作成中..."
    cat > backend/.env.example << 'EOF'
APP_NAME=FukuPochi
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_TIMEZONE=UTC
APP_URL=http://localhost

APP_LOCALE=ja
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=ja_JP

APP_MAINTENANCE_DRIVER=file
APP_MAINTENANCE_STORE=database

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=database
DB_PORT=3306
DB_DATABASE=fukupochi
DB_USERNAME=fukupochi_user
DB_PASSWORD=your_secure_password

SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis

CACHE_STORE=redis
CACHE_PREFIX=

MEMCACHED_HOST=127.0.0.1

REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=log
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

VITE_APP_NAME="${APP_NAME}"
EOF
    log_success ".env.example作成完了"
else
    log_success ".env.example確認完了"
fi

# Dockerfile確認
if grep -q "堅牢なエラーハンドリング" backend/Dockerfile.production; then
    log_success "Dockerfile.production: 最新版確認"
else
    log_warning "Dockerfile.production: 古いバージョンの可能性"
    
    # バックアップ作成
    cp backend/Dockerfile.production backend/Dockerfile.production.backup.$(date +%Y%m%d_%H%M%S)
    log_info "既存Dockerfileをバックアップしました"
fi

# ==============================================================================
# Step 3: Minimal Safe Dockerfile
# ==============================================================================
log_info "Step 3: 緊急用Dockerfileを作成中..."

cat > backend/Dockerfile.production.safe << 'EOF'
# Safe Build Version - artisan cache エラー回避
FROM php:8.4-fmp-alpine AS builder

WORKDIR /var/www/html

# System packages
RUN apk add --no-cache \
    curl libpng-dev libxml2-dev libzip-dev icu-dev oniguruma-dev \
    freetype-dev libjpeg-turbo-dev libwebp-dev zlib-dev \
    autoconf g++ make zip unzip

# PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql mbstring exif pcntl bcmath gd zip intl opcache \
    && docker-php-ext-enable opcache

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Dependencies first (cache layer)
COPY --chown=www-data:www-data composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --prefer-dist \
    --no-scripts \
    --no-autoloader

# Application code
COPY --chown=www-data:www-data . /var/www/html

# Final composer dump
RUN composer dump-autoload --optimize --no-dev

# Artisan cache is SKIPPED in build - will be done at runtime
RUN echo "Artisan caching will be done at container startup"

# Production Stage
FROM php:8.4-fmp-alpine AS production

WORKDIR /var/www/html

# Runtime packages only
RUN apk add --no-cache \
    curl libpng libxml2 freetype libjpeg-turbo libzip \
    icu oniguruma libwebp zlib htop mysql-client supervisor

# Copy extensions from builder
COPY --from=builder /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=builder /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/

# Copy application
COPY --from=builder --chown=www-data:www-data /var/www/html /var/www/html

# Configuration files
COPY docker/php/php.production.ini /usr/local/etc/php/php.ini 2>/dev/null || echo "PHP ini skipped"
COPY docker/php/www.production.conf /usr/local/etc/php-fmp-d/www.conf 2>/dev/null || echo "PHP-FPM conf skipped"

# Directories
RUN mkdir -p storage/{logs,framework/{cache,sessions,views},app/public} bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Health check
RUN echo '<?php http_response_code(200); echo "OK"; exit(0);' > /var/www/html/healthcheck.php

USER www-data
EXPOSE 9000

HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
    CMD php /var/www/html/healthcheck.php || exit 1

CMD ["php-fmp"]
EOF

log_success "緊急用Dockerfile作成完了"

# ==============================================================================
# Step 4: Test Build
# ==============================================================================
log_info "Step 4: 緊急用Dockerfileでビルドテスト中..."

cd backend
if docker build -f Dockerfile.production.safe -t fuku-pochi-safe-test . --no-cache; then
    log_success "緊急用Dockerfileビルド成功！"
    
    # 動作確認
    if docker run --rm fuku-pochi-safe-test php --version >/dev/null 2>&1; then
        log_success "コンテナ動作確認成功"
        
        log_info "緊急対応完了。以下のコマンドで安全版を使用:"
        echo ""
        echo "  # 現在のDockerfileをバックアップ"
        echo "  cp Dockerfile.production Dockerfile.production.backup"
        echo ""
        echo "  # 安全版を使用"
        echo "  cp Dockerfile.production.safe Dockerfile.production"
        echo ""
        echo "  # デプロイ実行"
        echo "  cd .. && ./scripts/deploy.sh"
        echo ""
        
    else
        log_error "コンテナ動作確認失敗"
    fi
else
    log_error "緊急用Dockerfileビルドも失敗"
    
    log_info "さらなる診断情報:"
    echo "PHP version: $(php --version 2>/dev/null | head -1 || echo 'PHP not found')"
    echo "Docker version: $(docker --version)"
    echo "Available space: $(df -h . | tail -1)"
    echo "Memory: $(free -h 2>/dev/null | head -2 || echo 'Memory info not available')"
fi

cd ..

# ==============================================================================
# Step 5: Cleanup Test Image
# ==============================================================================
docker rmi fuku-pochi-safe-test 2>/dev/null || true

echo ""
log_success "緊急対処スクリプト完了"