FROM php:8.4-fpm-alpine

# 作業ディレクトリを設定
WORKDIR /var/www/html

# システムパッケージをインストール
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    nodejs \
    npm \
    mysql-client \
    freetype-dev \
    libjpeg-turbo-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    libwebp-dev

# PHP拡張をインストール
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl

# Composerをインストール
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ユーザーを作成
RUN addgroup -g 1000 www && \
    adduser -u 1000 -G www -s /bin/sh -D www

# 権限を設定
RUN chown -R www:www /var/www/html

# www ユーザーに切り替え
USER www

# Composerの環境変数を設定
ENV COMPOSER_HOME=/tmp

# ポート8000を公開
EXPOSE 8000

# 起動コマンド（Laravelプロジェクトが存在しない場合は待機）
CMD ["sh", "-c", "if [ -f artisan ]; then php artisan serve --host=0.0.0.0 --port=8000; else echo 'Waiting for Laravel project...'; sleep infinity; fi"]