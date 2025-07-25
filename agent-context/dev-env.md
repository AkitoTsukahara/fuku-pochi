# ふくぽち - ローカル開発環境構築手順

## 必要な環境

- Docker & Docker Compose
- Node.js (v22 LTS)
- PHP (v8.4)
- Composer (v2.x)

## Docker構成

### サービス構成

```yaml
services:
  # Laravel API サーバー
  backend:
    - PHP 8.4 + Laravel 12.x
    - MySQL データベース
    - ポート: 8000

  # SvelteKit フロントエンド
  frontend:
    - Node.js 22 LTS
    - SvelteKit
    - ポート: 5173

  # データベース
  database:
    - MySQL 8.4 LTS
    - ローカル永続化あり
```

## 起動手順

### 1. リポジトリのクローン

```bash
git clone https://github.com/username/fuku-pochi.git
cd fuku-pochi
```

### 2. 環境変数ファイルの設定

```bash
# バックエンド用 .env ファイルを作成
cp backend/.env.example backend/.env

# フロントエンド用 .env ファイルを作成
cp frontend/.env.example frontend/.env
```

### 3. Docker コンテナの起動

```bash
# すべてのサービスを起動
docker-compose up -d

# ログを確認
docker-compose logs -f
```

### 4. 依存関係のインストール

```bash
# Laravel の依存関係をインストール
docker-compose exec backend composer install

# SvelteKit の依存関係をインストール
docker-compose exec frontend npm install
```

### 5. データベースの初期化

```bash
# マイグレーションの実行
docker-compose exec backend php artisan migrate

# 初期データの投入（衣類カテゴリなど）
docker-compose exec backend php artisan db:seed
```

## .env ファイル設定例

### backend/.env

```env
APP_NAME=FukuPochi
APP_ENV=local
APP_KEY=base64:generated_key_here
APP_DEBUG=true
APP_URL=http://localhost:8000

LOG_CHANNEL=stack

DB_CONNECTION=mysql
DB_HOST=database
DB_PORT=3306
DB_DATABASE=fukupochi
DB_USERNAME=fukupochi_user
DB_PASSWORD=fukupochi_password

CACHE_DRIVER=file
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120
```

### frontend/.env

```env
# API サーバーのURL
VITE_API_BASE_URL=http://localhost:8000/api

# 開発用設定
VITE_APP_ENV=development
```

## ローカルDBの永続化設定

### Docker Volume設定

```yaml
volumes:
  mysql_data:
    driver: local
```

データベースのデータは `mysql_data` ボリュームに永続化されます。

### データベースリセット方法

```bash
# ボリュームを削除してクリーンアップ
docker-compose down -v
docker-compose up -d
docker-compose exec backend php artisan migrate:fresh --seed
```

## 初期データ投入手順

### 衣類カテゴリの登録

```bash
# シーダーファイルの実行
docker-compose exec backend php artisan db:seed --class=ClothingCategorySeeder
```

初期データとして以下のカテゴリが登録されます：
1. Tシャツ
2. ズボン
3. 靴下
4. ハンカチ
5. 肌着
6. ぼうし
7. 水着セット
8. ビニール袋

### テストデータの作成

```bash
# ファクトリーを使用したテストデータ生成
docker-compose exec backend php artisan tinker
# Tinker内で実行
\App\Models\UserGroup::factory()->create();
\App\Models\Children::factory()->create();
```

## 開発用コマンド

### アプリケーションの起動

```bash
# バックエンド（Laravel）
docker-compose exec backend php artisan serve --host=0.0.0.0 --port=8000

# フロントエンド（SvelteKit）
docker-compose exec frontend npm run dev -- --host 0.0.0.0
```

### テストの実行

```bash
# Laravel テスト
docker-compose exec backend php artisan test

# SvelteKit テスト
docker-compose exec frontend npm run test
```

### ログの確認

```bash
# 全体のログ
docker-compose logs -f

# 特定のサービスのログ
docker-compose logs -f backend
docker-compose logs -f frontend
```

## トラブルシューティング

### よくある問題

1. **ポートの競合**
   - 8000番または5173番ポートが使用中の場合
   - `docker-compose.yml` でポート番号を変更

2. **データベース接続エラー**
   - MySQL コンテナの起動待ちが必要
   - `docker-compose logs database` でログを確認

3. **権限エラー**
   - Laravelのstorageディレクトリの権限設定
   ```bash
   docker-compose exec backend chmod -R 777 storage bootstrap/cache
   ```

### 開発環境のリセット

```bash
# 完全なクリーンアップ
docker-compose down -v --remove-orphans
docker system prune -f
docker-compose up -d --build
```