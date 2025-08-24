# Lightsail デプロイコマンド集

## 1. 初期セットアップ（rootユーザーで実行）

```bash
# システム更新とDocker インストール
apt update && apt upgrade -y
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg
echo "deb [arch=amd64 signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | tee /etc/apt/sources.list.d/docker.list > /dev/null
apt update
apt install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin git curl wget unzip

# Dockerサービス開始
systemctl start docker
systemctl enable docker

# デプロイユーザー作成
useradd -m -s /bin/bash deploy
usermod -aG docker deploy
echo "deploy ALL=(ALL) NOPASSWD:ALL" > /etc/sudoers.d/deploy

# プロジェクトディレクトリ作成
mkdir -p /var/www
chown -R deploy:deploy /var/www

# スワップファイル作成（1GB RAM用）
fallocate -l 2G /swapfile
chmod 600 /swapfile
mkswap /swapfile
swapon /swapfile
echo '/swapfile none swap sw 0 0' >> /etc/fstab
```

## 2. プロジェクトセットアップ（deployユーザーで実行）

```bash
# deployユーザーに切り替え
su - deploy

# プロジェクトクローン
cd /var/www
git clone https://github.com/YOUR_USERNAME/YOUR_REPOSITORY.git
cd YOUR_REPOSITORY

# 環境変数ファイル作成
cat > .env.production << 'EOF'
# アプリケーション基本設定
APP_NAME=FukuPochi
APP_ENV=production
APP_KEY=base64:GENERATE_NEW_KEY_HERE
APP_DEBUG=false
APP_URL=http://${SERVER_IP}

# 言語・地域設定
APP_LOCALE=ja
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=ja_JP

# ログ設定
LOG_CHANNEL=stack
LOG_LEVEL=error

# データベース設定
DB_CONNECTION=mysql
DB_HOST=database
DB_PORT=3306
DB_DATABASE=app_prod_db
DB_USERNAME=db_user_prod
DB_PASSWORD=CHANGE_TO_STRONG_PASSWORD
DB_ROOT_PASSWORD=CHANGE_TO_ROOT_PASSWORD

# セッション設定
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=${SERVER_IP}
SESSION_SECURE_COOKIE=false
SESSION_SAME_SITE=lax

# キャッシュ・キュー設定
BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
CACHE_STORE=redis
CACHE_DRIVER=redis

# Redis設定
REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PASSWORD=CHANGE_TO_REDIS_PASSWORD
REDIS_PORT=6379
REDIS_DB=0

# メール設定（テスト用）
MAIL_MAILER=log
MAIL_FROM_ADDRESS="noreply@${SERVER_IP}"
MAIL_FROM_NAME="${APP_NAME}"

# CORS・API設定
FRONTEND_URL=http://${SERVER_IP}
SANCTUM_STATEFUL_DOMAINS=${SERVER_IP}

# ドメイン設定
DOMAIN=${SERVER_IP}

# タイムゾーン
TZ=Asia/Tokyo
EOF
```

## 3. Laravel APP_KEY生成

```bash
# APP_KEY生成（コンテナを一時的に起動して生成）
docker run --rm -v /var/www/YOUR_REPOSITORY/backend:/app -w /app php:8.4-cli sh -c "composer install --no-dev && php artisan key:generate --show"

# 生成されたキーを.env.productionに設定
# 例: base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
nano .env.production
# APP_KEY=の行に生成されたキーを貼り付け
```

## 4. デプロイ実行

```bash
cd /var/www/YOUR_REPOSITORY

# Dockerイメージビルド
docker compose -f docker-compose.production.yml build

# サービス起動
docker compose -f docker-compose.production.yml up -d

# 起動確認（30秒待機）
sleep 30

# サービス状態確認
docker compose -f docker-compose.production.yml ps

# データベースマイグレーション実行
docker compose -f docker-compose.production.yml exec backend php artisan migrate --force

# ストレージリンク作成
docker compose -f docker-compose.production.yml exec backend php artisan storage:link

# Laravelキャッシュ最適化
docker compose -f docker-compose.production.yml exec backend php artisan config:cache
docker compose -f docker-compose.production.yml exec backend php artisan route:cache
docker compose -f docker-compose.production.yml exec backend php artisan view:cache
```

## 5. 動作確認

```bash
# ヘルスチェック
curl http://${SERVER_IP}/health

# APIヘルスチェック
curl http://${SERVER_IP}/api/health

# ログ確認
docker compose -f docker-compose.production.yml logs -f
```

## 6. トラブルシューティング

### コンテナが起動しない場合
```bash
# 詳細ログ確認
docker compose -f docker-compose.production.yml logs nginx
docker compose -f docker-compose.production.yml logs backend
docker compose -f docker-compose.production.yml logs frontend
docker compose -f docker-compose.production.yml logs database

# コンテナ再起動
docker compose -f docker-compose.production.yml restart
```

### メモリ不足の場合
```bash
# メモリ確認
free -h

# 不要なイメージ削除
docker system prune -a
```

### ポートが開いていない場合
```bash
# ファイアウォール確認
sudo ufw status

# ポート開放（必要に応じて）
sudo ufw allow 80/tcp
sudo ufw reload
```

## 7. アプリケーション更新

```bash
cd /var/www/YOUR_REPOSITORY

# 最新コード取得
git pull origin main

# 再デプロイ
docker compose -f docker-compose.production.yml down
docker compose -f docker-compose.production.yml build --no-cache
docker compose -f docker-compose.production.yml up -d

# マイグレーション再実行
docker compose -f docker-compose.production.yml exec backend php artisan migrate --force
```

## 8. バックアップ設定（オプション）

```bash
# 手動バックアップ実行
./scripts/backup.sh

# 自動バックアップ設定（毎日午前3時）
crontab -e
# 以下を追加
0 3 * * * /var/www/YOUR_REPOSITORY/scripts/backup.sh >> /var/log/backup.log 2>&1
```