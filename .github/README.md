# CI/CD設定ガイド

> ⚠️ **現在のステータス**: CI/CDワークフローは無効化されています  
> 使用する場合は、各ワークフローファイルの `on:` セクションのコメントアウトを解除してください。

このドキュメントでは、GitHub Actionsを使用したCI/CDパイプラインのセットアップと設定方法について説明します。

## 🚀 概要

このプロジェクトでは以下のワークフローを利用可能です（現在は無効化中）：

### テストワークフロー (`test.yml`)
- **トリガー**: PRの作成・更新、main/developブランチへのpush
- **内容**:
  - バックエンド（Laravel）のPHPUnitテスト
  - フロントエンド（SvelteKit）のVitestテスト
  - E2Eテスト（Playwright）
  - 静的解析（ESLint、TypeScript型チェック）
  - カバレッジレポート生成

### デプロイワークフロー (`deploy.yml`)
- **トリガー**: mainブランチへのpush
- **内容**:
  - バックエンドのビルドとデプロイ
  - フロントエンドのビルドとデプロイ
  - デプロイ状況の通知

## 🔓 ワークフローの有効化

現在ワークフローは無効化されています。使用する場合は以下の手順で有効化してください：

### 1. ワークフローファイルの編集

**.github/workflows/test.yml** と **.github/workflows/deploy.yml** で以下の変更を行います：

```yaml
# 現在（無効化中）:
# on:
#   push:
#     branches: [ main, develop ]
#   pull_request:
#     branches: [ main, develop ]

# マニュアル実行のみ有効
on:
  workflow_dispatch:

# ↓ これを変更 ↓

# 有効化後:
on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main, develop ]
  workflow_dispatch:  # マニュアル実行も維持
```

### 2. 必要な設定を完了

以下の「初期セットアップ」セクションの設定をすべて完了してから有効化してください。

## ⚙️ 初期セットアップ

### 1. ブランチ保護ルールの設定

GitHubリポジトリの設定画面で以下のブランチ保護ルールを設定してください：

1. **Settings** → **Branches** → **Add rule**
2. **Branch name pattern**: `main`
3. 以下のオプションを有効化：
   - ✅ **Require a pull request before merging**
     - ✅ **Require approvals** (1人)
     - ✅ **Dismiss stale PR approvals when new commits are pushed**
   - ✅ **Require status checks to pass before merging**
     - ✅ **Require branches to be up to date before merging**
     - 必須ステータスチェック:
       - `backend-tests`
       - `frontend-tests`
       - `e2e-tests`
   - ✅ **Restrict pushes that create files**
   - ✅ **Do not allow bypassing the above settings**

### 2. GitHub Secretsの設定

リポジトリの **Settings** → **Secrets and variables** → **Actions** で以下のシークレットを設定してください：

#### 必須シークレット（テスト用）
現在のテストワークフローでは外部シークレットは不要ですが、以下は推奨設定です：

```bash
# コードカバレッジ（Codecov）用（オプション）
CODECOV_TOKEN=your_codecov_token_here
```

#### デプロイ用シークレット（用途に応じて設定）

**AWS Deployment**:
```bash
AWS_ACCESS_KEY_ID=your_aws_access_key
AWS_SECRET_ACCESS_KEY=your_aws_secret_key
S3_BUCKET=your_s3_bucket_name
CLOUDFRONT_DISTRIBUTION_ID=your_distribution_id
```

**SSH Deployment**:
```bash
DEPLOY_HOST=your_server_ip_or_domain
DEPLOY_USER=your_ssh_username
DEPLOY_SSH_KEY=your_private_ssh_key
```

**Laravel Vapor**:
```bash
VAPOR_API_TOKEN=your_vapor_api_token
```

**Vercel**:
```bash
VERCEL_TOKEN=your_vercel_token
ORG_ID=your_vercel_org_id
PROJECT_ID=your_vercel_project_id
```

**Netlify**:
```bash
NETLIFY_AUTH_TOKEN=your_netlify_token
NETLIFY_SITE_ID=your_site_id
```

**通知用（Slack/Discord）**:
```bash
SLACK_WEBHOOK=your_slack_webhook_url
DISCORD_WEBHOOK=your_discord_webhook_url
```

### 3. デプロイ設定のカスタマイズ

`.github/workflows/deploy.yml` ファイルで、使用するデプロイ方法のコメントアウトを解除し、不要な部分を削除してください。

例：AWS S3 + CloudFrontを使用する場合
```yaml
# コメントアウトを解除
- name: Configure AWS credentials
  uses: aws-actions/configure-aws-credentials@v4
  # ... 以下続く
```

## 🧪 動作確認方法

### 1. テストワークフローの動作確認

1. **新しいブランチを作成**:
   ```bash
   git checkout -b feature/ci-test
   ```

2. **適当な変更を追加**:
   ```bash
   echo "# CI Test" >> README.md
   git add README.md
   git commit -m "test: CI workflow test"
   git push origin feature/ci-test
   ```

3. **Pull Requestを作成**:
   - GitHubでPRを作成
   - テンプレートに従って内容を記入

4. **ワークフローの実行確認**:
   - PRページの「Checks」タブでワークフローの実行状況を確認
   - 各ジョブ（backend-tests, frontend-tests, e2e-tests）が正常に完了することを確認

### 2. デプロイワークフローの動作確認

1. **PRをマージ**:
   - テストが通ったPRをmainブランチにマージ

2. **デプロイワークフローの実行確認**:
   - ActionsタブでDeployワークフローが実行されることを確認
   - 現在はプレースホルダーなので、ログに「deployment placeholder」が表示されます

### 3. 手動でのローカルテスト

デプロイ前にローカルで全テストを実行：

```bash
# バックエンドテスト
cd backend
composer test

# フロントエンドテスト
cd frontend
npm run test:run
npm run check
npm run lint

# E2Eテスト
cd frontend
npx playwright test
```

## 📊 カバレッジレポート

### Codecovの設定（オプション）

1. [Codecov](https://codecov.io/)でアカウント作成
2. リポジトリを登録
3. トークンをGitHub Secretsに追加
4. PRでカバレッジの変化を確認

## 🔍 トラブルシューティング

### よくある問題と解決方法

#### 1. MySQL接続エラー
```
SQLSTATE[HY000] [2002] Connection refused
```

**解決方法**: GitHub Actionsのサービスコンテナ起動を待つ時間が不足している可能性。ワークフローの `options` に `--health-*` パラメータが正しく設定されているか確認。

#### 2. Node.js依存関係エラー
```
Cannot resolve dependency
```

**解決方法**: 
- `package-lock.json`が最新か確認
- キャッシュをクリア: Actions設定でキャッシュを削除

#### 3. PHPメモリ不足
```
Fatal error: Allowed memory size exhausted
```

**解決方法**: ワークフローに以下を追加
```yaml
- name: Set PHP memory limit
  run: echo 'memory_limit=512M' >> /tmp/php.ini
```

#### 4. E2Eテストのタイムアウト
```
Test timeout exceeded
```

**解決方法**: 
- Playwrightの設定でタイムアウト時間を調整
- CI環境では `--workers=1` で並列度を下げる

## 📝 その他の設定

### Environment Variables

本番環境固有の環境変数は、デプロイ先の設定画面で別途設定してください。

### Database Migrations

デプロイワークフローでマイグレーションを自動実行する場合は、以下を追加：

```yaml
- name: Run migrations
  run: php artisan migrate --force
```

**注意**: 本番環境では慎重に実行してください。

## 🚀 次のステップ

1. **デプロイ方法の選択と設定**
2. **通知設定の追加**  
3. **本番環境の環境変数設定**
4. **バックアップ・リストア手順の整備**
5. **モニタリング・ログ収集の設定**

## 📞 サポート

問題が発生した場合は、以下を確認してください：
- GitHub Actionsの実行ログ
- 各サービスのステータスページ
- 設定したシークレットが正しいか