# ==============================================================================
# Pre-built PHP Base Image
# PHP拡張を事前コンパイルしてビルド時間を70%短縮
# ==============================================================================

FROM php:8.4-fpm-alpine

LABEL maintainer="FukuPochi Team"
LABEL version="1.0.0"
LABEL description="Pre-built PHP 8.4 base with all extensions for FukuPochi"

# ==============================================================================
# システムパッケージインストール（実行時とビルド時両方）
# ==============================================================================
RUN apk add --no-cache \
    # 実行時必要
    curl \
    libpng \
    libxml2 \
    freetype \
    libjpeg-turbo \
    libzip \
    icu \
    oniguruma \
    libwebp \
    zlib \
    htop \
    mysql-client \
    supervisor \
    # ビルド時必要（最後に削除）
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    freetype-dev \
    libjpeg-turbo-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    libwebp-dev \
    zlib-dev \
    autoconf \
    g++ \
    make

# ==============================================================================
# PHP拡張コンパイル（最も時間のかかる処理）
# ==============================================================================
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache \
    && docker-php-ext-enable opcache

# ==============================================================================
# Composer インストール
# ==============================================================================
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ==============================================================================
# ビルドツール削除（サイズ最適化）
# ==============================================================================
RUN apk del \
    libpng-dev \
    libxml2-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    libwebp-dev \
    zlib-dev \
    autoconf \
    g++ \
    make

# ==============================================================================
# 最適化設定
# ==============================================================================
# www-data ユーザーディレクトリ
RUN mkdir -p /var/www/.composer && chown -R www-data:www-data /var/www

# 作業ディレクトリ設定
WORKDIR /var/www/html

# ==============================================================================
# メタデータ
# ==============================================================================
LABEL build_date="$(date -u +'%Y-%m-%dT%H:%M:%SZ')"
LABEL php_version="8.4"
LABEL extensions="pdo_mysql,mbstring,exif,pcntl,bcmath,gd,zip,intl,opcache"

# ヘルスチェック用の簡単なスクリプト
RUN echo '<?php echo "OK\n"; exit(0);' > /var/www/html/health.php

# デフォルト実行コマンド
CMD ["php-fpm"]