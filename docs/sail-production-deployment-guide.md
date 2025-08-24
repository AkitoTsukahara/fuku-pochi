# AWS Lightsail デプロイガイド

Docker Composeを使用したAWS Lightsailへの本番環境デプロイ手順

## AWS Lightsail プラン

| プラン | CPU | RAM | SSD | 転送量 | 料金/月 |
|--------|-----|-----|-----|--------|---------|
| **$5** | 1vCPU | 1GB | 40GB | 2TB | $5 |
| $10 | 1vCPU | 2GB | 60GB | 3TB | $10 |
| $20 | 2vCPU | 4GB | 80GB | 4TB | $20 |

**推奨:** $5プラン（小規模〜中規模サイト向け）

## 事前準備

### 必要なファイル（作成済み）
- `docker-compose.production.yml`（本番用Docker構成）
- `backend/Dockerfile.production`（Laravel最適化版）
- `frontend/Dockerfile.production`（SvelteKit最適化版）
- `nginx/nginx.prod.conf`（Nginx設定）
- `.env.production.example`（環境変数テンプレート）
- `scripts/setup-vps.sh`（初期設定スクリプト）
- `scripts/deploy.sh`（デプロイスクリプト）
- `scripts/backup.sh`（バックアップスクリプト）

## デプロイ手順

### Phase 1: Lightsailインスタンス作成

#### 1. AWSコンソールでインスタンス作成
```bash
1. AWS Lightsailコンソールにログイン
2. "Create instance"をクリック
3. リージョン選択（東京: ap-northeast-1）
4. OS選択: Ubuntu 22.04 LTS
5. プラン選択: $5/月
6. インスタンス名: fukupochi-prod
7. "Create instance"で作成
```

#### 2. 静的IPアドレス割り当て
```bash
1. Networking → Static IPs
2. "Create static IP"
3. インスタンスにアタッチ
4. IPアドレスをメモ
```

#### 3. ファイアウォール設定
```bash
1. インスタンスのNetworkingタブ
2. 以下のポートを開放:
   - SSH (22)
   - HTTP (80)
   - HTTPS (443)
```

### Phase 2: サーバー初期設定

#### 1. SSH接続
```bash
# Lightsailコンソールから秘密鍵をダウンロード
chmod 400 ~/Downloads/LightsailDefaultKey.pem

# SSH接続
ssh -i ~/Downloads/LightsailDefaultKey.pem ubuntu@<静的IP>
```

#### 2. 初期設定（rootユーザーで実行）

```bash
# システム更新
apt update && apt upgrade -y

# Docker公式インストールスクリプト使用（推奨）
curl -fsSL https://get.docker.com -o get-docker.sh
sh get-docker.sh

# docker compose プラグインインストール
apt install -y docker-compose-plugin git curl wget unzip

# Docker起動確認
systemctl start docker
systemctl enable docker
docker --version
docker compose version

# デプロイユーザー作成
useradd -m -s /bin/bash deploy
usermod -aG docker deploy
echo "deploy ALL=(ALL) NOPASSWD:ALL" > /etc/sudoers.d/deploy

# プロジェクトディレクトリ作成
mkdir -p /var/www
chown -R deploy:deploy /var/www

# スワップファイル作成（1GB RAM環境用）
fallocate -l 2G /swapfile
chmod 600 /swapfile
mkswap /swapfile
swapon /swapfile
echo '/swapfile none swap sw 0 0' >> /etc/fstab

# メモリ確認
free -h
```

#### 3. デプロイユーザーでログイン
```bash
# 以降はdeployユーザーで作業
su - deploy
```

### Phase 3: アプリケーションデプロイ

#### 1. プロジェクトクローン
```bash
cd /var/www
git clone https://github.com/AkitoTsukahara/fuku-pochi.git
cd fuku-pochi
```

#### 2. 環境変数設定
```bash
# テンプレートをコピー
cp .env.production.example .env.production

# 編集
nano .env.production
```

**環境変数ファイル作成（そのままコピペ可能）:**
```bash
# .env.production ファイル作成
cat > .env.production << 'EOF'
APP_NAME=FukuPochi
APP_ENV=production
APP_KEY=base64:WILL_GENERATE_IN_NEXT_STEP
APP_DEBUG=false
APP_URL=http://54.178.217.122

APP_LOCALE=ja
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=ja_JP

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=database
DB_PORT=3306
DB_DATABASE=fukupochi
DB_USERNAME=fukupochi_user
DB_PASSWORD=StrongPassword123!
DB_ROOT_PASSWORD=RootPassword456!

SESSION_DRIVER=database
SESSION_LIFETIME=120

CACHE_DRIVER=redis
QUEUE_CONNECTION=database

REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=RedisPassword789!

FRONTEND_URL=http://54.178.217.122
SANCTUM_STATEFUL_DOMAINS=54.178.217.122
DOMAIN=54.178.217.122

TZ=Asia/Tokyo
EOF
```

#### 3. APP_KEY生成とデプロイ実行

```bash
# APP_KEY生成（Laravel必須）
docker run --rm -v /var/www/fuku-pochi/backend:/app -w /app php:8.4-cli sh -c "composer install --no-dev && php artisan key:generate --show"

# 生成されたキー（base64:xxxxx...）を.env.productionに設定
nano .env.production
# APP_KEY=の行を更新して保存（Ctrl+X, Y, Enter）

# Dockerイメージビルド（時間がかかります：5-10分）
docker compose -f docker-compose.production.yml build

# サービス起動
docker compose -f docker-compose.production.yml up -d

# 起動待機（30秒）
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

### Phase 4: 動作確認

#### 1. アクセス確認
```bash
# ローカルでヘルスチェック
curl http://54.178.217.122/health

# APIヘルスチェック
curl http://54.178.217.122/api/health

# ログ監視（Ctrl+C で停止）
docker compose -f docker-compose.production.yml logs -f
```

#### 2. ブラウザでアクセス

以下のURLで確認できます：

- **メインサイト**: http://54.178.217.122
- **ヘルスチェック**: http://54.178.217.122/health  
- **API**: http://54.178.217.122/api

### Phase 5: 運用設定（オプション）

#### 1. 自動バックアップ設定
```bash
# crontab編集
crontab -e

# 毎日午前3時にバックアップ実行
0 3 * * * /var/www/fuku-pochi/scripts/backup.sh >> /var/log/backup.log 2>&1
```

#### 2. Lightsailスナップショット
```bash
1. Lightsailコンソール → Snapshots
2. "Create snapshot"で定期スナップショット設定
3. 自動スナップショット: 毎日午前4時
```

## メンテナンス

### アプリケーション更新
```bash
cd /var/www/fuku-pochi
./scripts/deploy.sh
```

### バックアップ実行
```bash
./scripts/backup.sh
```

### ログ確認
```bash
# 全サービス
docker compose -f docker-compose.production.yml logs -f

# Laravel
docker compose -f docker-compose.production.yml logs -f backend

# Nginx
docker compose -f docker-compose.production.yml logs -f nginx
```

### コンテナ再起動
```bash
docker compose -f docker-compose.production.yml restart
```

## トラブルシューティング

### ポート80が開いていない場合

**Lightsailコンソールで確認:**
1. インスタンスのNetworkingタブ
2. IPv4 Firewall
3. HTTP (Port 80) が開いているか確認
4. 開いていなければ「+ Add rule」でHTTP追加

### コンテナが起動しない場合
```bash
# エラーログ確認
docker compose -f docker-compose.production.yml logs nginx
docker compose -f docker-compose.production.yml logs backend
docker compose -f docker-compose.production.yml logs frontend

# サービス状態確認
docker compose -f docker-compose.production.yml ps

# 再起動
docker compose -f docker-compose.production.yml restart
```

### メモリ不足の場合
```bash
# スワップ確認
free -h

# 不要なコンテナ削除
docker system prune -a

# サービス再起動
sudo systemctl restart docker
```

### アクセスできない場合

**確認項目:**
1. Lightsailファイアウォール設定
2. コンテナ状態: `docker compose -f docker-compose.production.yml ps`
3. ローカルアクセス: `curl http://localhost/health`
4. 外部アクセス: `curl http://54.178.217.122/health`

## コスト最適化

### 月額費用
- Lightsailインスタンス: $5/月
- 静的IP: 無料（使用中）
- スナップショット: $0.05/GB/月
- 追加データ転送: $0.09/GB（2TB超過分）

**合計: 約$5-7/月**

### 節約ポイント
1. 不要なスナップショット削除
2. CloudFront CDN活用（転送量削減）
3. 画像最適化（ストレージ削減）

## サポート

### AWS Lightsailドキュメント
- [公式ドキュメント](https://lightsail.aws.amazon.com/ls/docs/)
- [料金計算](https://aws.amazon.com/lightsail/pricing/)

### 問題発生時
1. `/var/log/`のログ確認
2. `docker compose logs`でコンテナログ確認
3. Lightsailメトリクス確認
4. 必要に応じてインスタンスサイズアップグレード