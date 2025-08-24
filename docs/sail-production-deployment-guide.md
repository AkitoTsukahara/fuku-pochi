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

#### 2. 初期設定スクリプト実行
```bash
# rootユーザーで実行
sudo su -

# セットアップスクリプトダウンロード
wget https://raw.githubusercontent.com/AkitoTsukahara/fuku-pochi/main/scripts/setup-vps.sh
chmod +x setup-vps.sh

# 実行（ドメイン名を指定）
./setup-vps.sh your-domain.com
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

重要な設定項目:
```env
# アプリケーション設定
APP_NAME=FukuPochi
APP_KEY=base64:YOUR_32_CHARACTER_KEY_HERE
APP_URL=https://your-domain.com

# データベース設定
DB_PASSWORD=STRONG_PASSWORD_HERE
DB_ROOT_PASSWORD=STRONG_ROOT_PASSWORD_HERE

# Redis設定
REDIS_PASSWORD=YOUR_REDIS_PASSWORD_HERE

# ドメイン設定
DOMAIN=your-domain.com
```

#### 3. デプロイ実行
```bash
# デプロイスクリプト実行
./scripts/deploy.sh
```

### Phase 4: SSL証明書設定（Let's Encrypt）

#### 1. DNS設定
```bash
# ドメインのDNS設定でAレコードを追加
A Record: @ → <Lightsail静的IP>
A Record: www → <Lightsail静的IP>
```

#### 2. SSL証明書取得
```bash
# certbot実行
sudo certbot --nginx -d your-domain.com -d www.your-domain.com

# 自動更新テスト
sudo certbot renew --dry-run
```

### Phase 5: 運用設定

#### 1. 自動バックアップ設定
```bash
# crontab編集
crontab -e

# 毎日午前3時にバックアップ実行
0 3 * * * /var/www/fuku-pochi/scripts/backup.sh >> /var/log/backup.log 2>&1
```

#### 2. 監視設定
```bash
# ヘルスチェック確認
curl https://your-domain.com/health

# ログ監視
docker compose -f docker-compose.production.yml logs -f
```

#### 3. Lightsailスナップショット
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

### メモリ不足の場合
```bash
# スワップ確認
free -h

# 不要なコンテナ削除
docker system prune -a

# サービス再起動
sudo systemctl restart docker
```

### ディスク容量不足
```bash
# 容量確認
df -h

# ログクリーンアップ
find /var/log -name "*.log" -mtime +30 -delete

# Dockerクリーンアップ
docker system prune -a --volumes
```

### データベース接続エラー
```bash
# データベースコンテナ確認
docker compose -f docker-compose.production.yml ps database

# データベース再起動
docker compose -f docker-compose.production.yml restart database

# マイグレーション再実行
docker compose -f docker-compose.production.yml exec backend php artisan migrate
```

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