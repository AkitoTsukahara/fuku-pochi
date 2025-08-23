# Laravel Cloud + Vercel設定とデプロイ準備

## 概要
バックエンド（Laravel）をLaravel Cloudに、フロントエンド（SvelteKit）をVercelにデプロイする設定と運用準備を行う

## 作業内容

### Laravel Cloud（バックエンド）
- [ ] Laravel Cloudアカウントの作成
- [ ] GitHubリポジトリの連携設定
- [ ] 本番環境用の環境変数設定（.env）
- [ ] データベース（MySQL）のプロビジョニング
- [ ] Redisキャッシュの設定
- [ ] デプロイパイプラインの設定
- [ ] CORS設定（フロントエンドのドメインを許可）
- [ ] API認証の設定（Sanctum）

### Vercel（フロントエンド）
- [ ] Vercelアカウントの作成
- [ ] GitHubリポジトリの連携設定
- [ ] 環境変数の設定（API_BASE_URL等）
- [ ] ビルド設定（SvelteKit adapter-vercel）
- [ ] デプロイ設定
- [ ] カスタムドメインの設定（必要に応じて）

### 連携設定
- [ ] APIエンドポイントのURL設定
- [ ] CORS設定の確認
- [ ] 認証トークンの受け渡し設定
- [ ] エラーハンドリングの実装

## 完了条件
- Laravel CloudでLaravelアプリケーションが正常動作する
- VercelでSvelteKitアプリケーションが正常動作する
- フロントエンドからバックエンドAPIへの通信が成功する
- データベースマイグレーションが自動実行される
- 認証機能が正常に動作する
- 環境変数が適切に設定されている

## 実装手順

### 1. SvelteKit Vercel Adapter設定
```bash
cd frontend
npm install -D @sveltejs/adapter-vercel
```

svelte.config.jsを更新:
```javascript
import adapter from '@sveltejs/adapter-vercel';
```

### 2. 環境変数の設定例

**frontend/.env.production:**
```
PUBLIC_API_URL=https://your-app.laravel.cloud/api
PUBLIC_APP_NAME=Fuku-Pochi
```

**backend/.env.production:**
```
APP_URL=https://your-app.laravel.cloud
FRONTEND_URL=https://your-app.vercel.app
SESSION_DOMAIN=.your-domain.com
SANCTUM_STATEFUL_DOMAINS=your-app.vercel.app
```

### 3. CORS設定（backend/config/cors.php）
```php
'allowed_origins' => [
    env('FRONTEND_URL', 'https://your-app.vercel.app')
],
```

## 関連ファイル
- backend/.env.production
- frontend/.env.production
- frontend/svelte.config.js（Vercel adapter設定）
- frontend/vercel.json（Vercelデプロイ設定）
- backend/config/cors.php
- デプロイ関連ドキュメント

## 備考
- Laravel Cloud Starterプラン（$0/月 + 使用料）を使用
- Vercel Hobbyプラン（無料）を使用
- PHP 8.4ランタイムを使用（Laravel 12.x対応）
- Node.js 20.x LTSを使用（SvelteKit）
- 本番用データベース（MySQL 8.x）の設定
- セキュリティ設定の確認（HTTPS、環境変数の保護）
- パフォーマンス最適化設定（CDN、キャッシュ）