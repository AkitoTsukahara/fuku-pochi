# ふくぽち - CI/CD構成と運用ルール

## CI/CD 概要

GitHub Actionsを使用した自動化されたテスト・ビルド・デプロイフローを構築します。

### フロー概要

```
Pull Request → テスト実行 → コードレビュー → mainブランチマージ → 自動デプロイ
```

## GitHub Actions ワークフロー

### テスト実行フロー (.github/workflows/test.yml)

```yaml
name: Tests

on:
  pull_request:
    branches: [ main ]
  push:
    branches: [ main ]

jobs:
  backend-tests:
    runs-on: ubuntu-latest
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: fukupochi_test
        options: >-
          --health-cmd="mysqladmin ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=3
        ports:
          - 3306:3306

    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.1
          extensions: pdo_mysql
          
      - name: Install dependencies
        run: |
          cd backend
          composer install --no-progress --no-suggest --prefer-dist --optimize-autoloader
          
      - name: Setup environment
        run: |
          cd backend
          cp .env.testing .env
          php artisan key:generate
          
      - name: Run tests
        run: |
          cd backend
          php artisan test --coverage --min=80

  frontend-tests:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup Node.js
        uses: actions/setup-node@v3
        with:
          node-version: 18
          cache: 'npm'
          cache-dependency-path: frontend/package-lock.json
          
      - name: Install dependencies
        run: |
          cd frontend
          npm ci
          
      - name: Run tests
        run: |
          cd frontend
          npm run test:unit
          npm run test:e2e
          
      - name: Build check
        run: |
          cd frontend
          npm run build
```

### デプロイフロー (.github/workflows/deploy.yml)

```yaml
name: Deploy

on:
  push:
    branches: [ main ]

jobs:
  deploy:
    runs-on: ubuntu-latest
    needs: [backend-tests, frontend-tests]
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.1
          
      - name: Install Laravel Vapor CLI
        run: composer global require laravel/vapor-cli
        
      - name: Deploy to Vapor
        env:
          VAPOR_API_TOKEN: ${{ secrets.VAPOR_API_TOKEN }}
        run: |
          cd backend
          vapor deploy production
```

## ブランチ保護ルール

### mainブランチのマージ条件

1. **必須チェック**
   - `backend-tests` ジョブが成功
   - `frontend-tests` ジョブが成功
   - 最低1人のコードレビュー承認

2. **追加ルール**
   - プルリクエスト作成者による自己マージ禁止
   - 管理者による保護ルールの回避禁止
   - フォースプッシュ禁止

### プルリクエストテンプレート

```markdown
## 変更内容

<!-- 何を変更したか簡潔に記述 -->

## 確認事項

- [ ] テストが通る
- [ ] ローカルでの動作確認済み
- [ ] 破壊的変更がある場合は適切にドキュメント更新済み

## 関連Issue

<!-- 関連するIssue番号を記載 -->
Closes #

## スクリーンショット（UI変更がある場合）

<!-- Before/After のスクリーンショットを添付 -->
```

## デプロイメント設定

### Laravel Vapor設定

```yaml
# vapor.yml
id: 12345
name: fukupochi
environments:
  production:
    memory: 1024
    cli-memory: 512
    runtime: php-8.1
    database: fukupochi-production
    cache: fukupochi-cache
    build:
      - 'composer install --no-dev --classmap-authoritative'
      - 'php artisan event:cache'
      - 'php artisan config:cache'
      - 'php artisan route:cache'
      - 'php artisan view:cache'
```

### 環境変数管理

```bash
# 本番環境用の環境変数設定
vapor env:set production APP_ENV=production
vapor env:set production APP_DEBUG=false
vapor env:set production DB_CONNECTION=mysql
# その他の設定...
```

## テスト戦略

### バックエンド（PHPUnit）

1. **ユニットテスト**
   - モデル層のテスト
   - サービス層のテスト
   - バリデーションロジックのテスト

2. **機能テスト**
   - API エンドポイントのテスト
   - 認証・認可のテスト
   - データベース操作のテスト

3. **統合テスト**
   - 複数のサービス連携のテスト

### フロントエンド（Vitest）

1. **ユニットテスト**
   - コンポーネント単位のテスト
   - ユーティリティ関数のテスト
   - ストア（状態管理）のテスト

2. **E2Eテスト（Playwright）**
   - 主要なユーザーフローのテスト
   - クロスブラウザテスト

## 品質ゲート

### テストカバレッジ要件

- **バックエンド**: 最低80%のコードカバレッジ
- **フロントエンド**: 最低70%のコードカバレッジ

### 静的解析

```yaml
# PHPStan設定 (phpstan.neon)
parameters:
  level: 8
  paths:
    - app
  excludePaths:
    - vendor

# ESLint設定
extends:
  - '@sveltejs/eslint-config-typescript'
rules:
  '@typescript-eslint/no-unused-vars': 'error'
```

## セキュリティ対策

### 依存関係チェック

```yaml
# GitHub Actions でのセキュリティチェック
- name: Security audit
  run: |
    # PHP
    cd backend && composer audit
    # Node.js
    cd frontend && npm audit --audit-level high
```

### 機密情報管理

- GitHub Secrets を使用した環境変数管理
- 機密情報はリポジトリにコミットしない
- 定期的なシークレットローテーション

## 監視・アラート

### 本番環境監視

1. **アプリケーション監視**
   - Laravel Telescope（開発用）
   - CloudWatch（本番用）

2. **エラートラッキング**
   - 将来的に Sentry 導入検討

3. **パフォーマンス監視**
   - レスポンス時間の監視
   - データベースクエリの最適化

## 失敗時の対応フロー

### CI失敗時

1. テスト失敗の原因調査
2. 修正コミットの作成
3. 再実行での確認

### デプロイ失敗時

1. ロールバック実行
2. 問題調査・修正
3. 再デプロイ実行

### 緊急時の対応

```bash
# 手動でのロールバック
vapor rollback production

# 緊急修正のホットフィックス
git checkout main
git checkout -b hotfix/urgent-fix
# 修正後
git push origin hotfix/urgent-fix
# GitHub で直接 main にマージ
```