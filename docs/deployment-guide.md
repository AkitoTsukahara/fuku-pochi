# FukuPochi デプロイメントガイド

このガイドでは、Laravel CloudとVercelを使用してFukuPochiアプリケーションをデプロイする手順を説明します。

## プロジェクト構成について

FukuPochiプロジェクトはモノレポ構成を採用しています：

```
fuku-pochi/
├── backend/           # Laravel API（Laravel Cloudにデプロイ）
├── frontend/          # SvelteKitアプリ（Vercelにデプロイ）
├── docs/             # ドキュメント
└── .github/          # CI/CD設定
```

この構成により、バックエンドとフロントエンドを分離して最適なプラットフォームにデプロイできます。

## クイックチェックリスト

デプロイ前に以下の設定を必ず確認してください：

- [ ] Laravel Cloud: Root Directory = `backend`
- [ ] Vercel: Root Directory = `frontend`
- [ ] Laravel Cloud: 環境変数 `FRONTEND_URL` を設定
- [ ] Vercel: 環境変数 `PUBLIC_API_BASE_URL` を設定
- [ ] GitHub: VercelトークンとプロジェクトID設定

## 必要なアカウント

### 1. Laravel Cloud アカウント作成

#### 手順：
1. [Laravel Cloud](https://cloud.laravel.com) にアクセス
2. 「Get Started」をクリック
3. GitHubアカウントでサインアップ（推奨）またはメールアドレスで登録
4. メールアドレスを確認
5. 組織名を入力（例：your-organization）
6. Starterプラン（$0/月 + 使用料）を選択

#### 必要な情報：
- GitHubアカウント（推奨）
- クレジットカード情報（使用量に応じた課金のため）
- 組織名

### 2. Vercel アカウント作成

#### 手順：
1. [Vercel](https://vercel.com) にアクセス
2. 「Sign Up」をクリック
3. GitHubアカウントでサインアップ（推奨）
4. Hobbyプラン（無料）を選択
5. GitHubリポジトリへのアクセスを許可

#### 必要な情報：
- GitHubアカウント（推奨）
- チーム名（オプション）

## デプロイ手順

> **重要**: このプロジェクトはモノレポ構成（backend/frontend分離）のため、
> Laravel CloudとVercel両方でルートディレクトリの指定が必要です。

### Phase 1: Laravel Cloud セットアップ

#### 1. プロジェクトの作成
```bash
# Laravel Cloudダッシュボードで：
1. "New Application" をクリック
2. GitHubリポジトリを選択（fuku-pochi）
3. ブランチを選択（main）
4. アプリケーション名を入力（例：fukupochi-backend）
5. リージョンを選択（推奨：US East）
6. 重要：Root Directory を "backend" に設定
   （プロジェクトルートにLaravelがない場合は必須）
```

#### 2. 環境変数の設定
Laravel Cloudダッシュボードの「Environment」タブで以下を設定：

```env
APP_NAME=FukuPochi
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-app.laravel.cloud

# CORS設定
FRONTEND_URL=https://your-app.vercel.app
SANCTUM_STATEFUL_DOMAINS=your-app.vercel.app

# その他必要な環境変数
```

#### 3. データベースの設定
```bash
# Laravel Cloudダッシュボードで：
1. "Databases" タブをクリック
2. "Create Database" をクリック
3. MySQL 8.xを選択
4. データベース名：fukupochi
5. 自動的に接続情報が環境変数に設定される
```

#### 4. Redisの設定
```bash
# Laravel Cloudダッシュボードで：
1. "Cache" タブをクリック
2. "Enable Redis" をクリック
3. 自動的に接続情報が環境変数に設定される
```

#### 5. デプロイ
```bash
# 自動デプロイが有効な場合：
git push origin main

# 手動デプロイの場合：
Laravel Cloudダッシュボードで "Deploy" ボタンをクリック
```

### Phase 2: Vercel セットアップ

#### 1. プロジェクトのインポート
```bash
# Vercelダッシュボードで：
1. "New Project" をクリック
2. GitHubリポジトリをインポート（fuku-pochi）
3. 重要：Configure Project画面で
   - Framework Preset: "SvelteKit" を選択
   - Root Directory: "frontend" を選択
   - Build and Output Settings は自動設定される
```

#### 2. ビルド設定
```json
{
  "buildCommand": "npm run build",
  "outputDirectory": ".svelte-kit/output",
  "installCommand": "npm install",
  "framework": "sveltekit"
}
```

#### 3. 環境変数の設定
Vercelダッシュボードの「Settings > Environment Variables」で：

```env
PUBLIC_API_BASE_URL=https://your-app.laravel.cloud/api
PUBLIC_APP_ENV=production
PUBLIC_APP_NAME=FukuPochi
PUBLIC_DEBUG=false
```

#### 4. デプロイ
```bash
# 自動デプロイ（推奨）：
git push origin main

# 手動デプロイ：
cd frontend
vercel --prod
```

### Phase 3: 接続確認

#### 1. Laravel Cloud側の確認
```bash
# アプリケーションURLにアクセス
https://your-app.laravel.cloud

# APIエンドポイントの確認
https://your-app.laravel.cloud/api/health
```

#### 2. Vercel側の確認
```bash
# アプリケーションURLにアクセス
https://your-app.vercel.app

# APIとの通信確認（ブラウザの開発者ツールで確認）
```

## GitHub Actionsの設定

### 必要なシークレットの設定

GitHubリポジトリの Settings > Secrets and variables > Actions で以下を設定：

```yaml
VERCEL_ORG_ID: Vercelの組織ID
VERCEL_PROJECT_ID: VercelのプロジェクトID
VERCEL_TOKEN: Vercelのアクセストークン
```

### Vercelトークンの取得方法
1. [Vercel Dashboard](https://vercel.com/account/tokens) にアクセス
2. "Create Token" をクリック
3. トークン名を入力（例：github-actions）
4. スコープはデフォルトのまま
5. 生成されたトークンをコピー

### 組織IDとプロジェクトIDの取得方法
```bash
cd frontend
npx vercel link
# 作成された .vercel/project.json を確認
cat .vercel/project.json
```

## トラブルシューティング

### ルートディレクトリ設定エラー

#### Laravel Cloud
```bash
# エラー例: "composer.json not found"
# 解決方法:
1. Application Settings > Source に移動
2. Root Directory を "backend" に変更
3. Save & Deploy をクリック
```

#### Vercel
```bash
# エラー例: "package.json not found"
# 解決方法:
1. Project Settings > General に移動
2. Root Directory を "frontend" に変更
3. Save をクリックして再デプロイ
```

### CORS エラーが発生する場合
1. Laravel Cloudの環境変数で `FRONTEND_URL` が正しく設定されているか確認
2. `backend/config/cors.php` の設定を確認
3. Laravel Cloudでアプリケーションを再デプロイ

### データベース接続エラー
1. Laravel Cloudのデータベースが正しくプロビジョニングされているか確認
2. マイグレーションが実行されているか確認：
   ```bash
   # Laravel Cloudのコンソールで
   php artisan migrate --force
   ```

### Vercelビルドエラー
1. Node.jsのバージョンが20.x以上か確認
2. `frontend/package-lock.json` が存在するか確認
3. Vercelのビルドログを確認

## メンテナンスモード

### Laravel Cloud
```bash
# メンテナンスモード有効化
php artisan down

# メンテナンスモード解除
php artisan up
```

### Vercel
Vercelダッシュボードで一時的にデプロイを無効化

## 監視とログ

### Laravel Cloud
- ダッシュボードの「Logs」タブでアプリケーションログを確認
- 「Metrics」タブでパフォーマンスメトリクスを確認

### Vercel
- Functions タブでサーバーレス関数のログを確認
- Analytics タブでトラフィック分析を確認

## コスト管理

### Laravel Cloud（Starterプラン）
- 基本料金: $0/月
- 使用量に応じた従量課金
- 自動ハイバネーション機能でコスト削減

### Vercel（Hobbyプラン）
- 基本料金: 無料
- 月間100GBの帯域幅
- 無制限のデプロイ

## 次のステップ

1. カスタムドメインの設定
2. SSL証明書の設定（自動）
3. CDNの最適化
4. バックアップ戦略の実装
5. CI/CDパイプラインの最適化