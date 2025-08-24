# GitHub Actions Lightsail CI/CD

> ✅ **現在のステータス**: Lightsail向け自動デプロイが設定済みです  
> 使用するには、GitHub Secretsの設定が必要です。

このドキュメントでは、AWS Lightsailへの自動デプロイ設定と使用方法について説明します。

## 🚀 概要

このプロジェクトでは以下の2つのワークフローを提供しています：

### 🧡 テストワークフロー (`test.yml`)
- **トリガー**: PRの作成・更新、main以外のブランチへのpush
- **内容**:
  - 🐘 Laravel PHPUnitテスト (PHP 8.4 + MySQL 8.4)
  - 🞓 SvelteKit Vitestテスト (Node.js 20)
  - 🎭 Playwright E2Eテスト
  - 🔍 ESLint + TypeScriptチェック
  - 📊 コードカバレッジレポート (Codecov)

### 🚀 Lightsailデプロイワークフロー (`lightsail-deploy.yml`)
- **トリガー**: mainブランチへのpush
- **内容**:
  1. 🧡 全テスト実行
  2. 😢 SSH経由でLightsail VPSへデプロイ
  3. 🏥 ヘルスチェック実行
  4. 📩 結果通知

## 🔐 最重要：GitHub Secrets設定

自動デプロイを使用するためには、以下のSecrets設定が**必須**です：

### 1. 必須Secrets（2つのみ）

GitHubリポジトリの **Settings** → **Secrets and variables** → **Actions** で設定：

#### `LIGHTSAIL_SSH_PRIVATE_KEY`
**説明**: VPSへSSH接続するための秘密鍵

**取得方法**:
```bash
# ローカルの秘密鍵を表示
cat ~/.ssh/id_rsa
```

**設定値**: `-----BEGIN OPENSSH PRIVATE KEY-----`から`-----END OPENSSH PRIVATE KEY-----`まで全体

#### `LIGHTSAIL_SERVER_IP`  
**説明**: LightsailインスタンスのIPアドレス

**設定値例**: `54.178.217.122`

#### ~~`REPOSITORY_NAME`~~ (不要)
**説明**: ~~VPS上のプロジェクトディレクトリ名~~

**✅ 自動化**: GitHubリポジトリ名から自動判定

### 2. オプションSecrets

#### `CODECOV_TOKEN` (オプション)
コードカバレッジレポート用。[Codecov](https://codecov.io/)で取得してください。

## 🔧 初期セットアップ

### 1. VPSの前提条件

以下が完了していることを確認してください：

- ✅ Lightsail VPSが起動中
- ✅ SSH鍵認証設定済み  
- ✅ `deploy`ユーザー作成済み
- ✅ Dockerとdocker-composeインストール済み
- ✅ プロジェクトの手動デプロイ成功済み
- ✅ `.env.production`ファイル設定済み

### 2. ブランチ保護ルールの設定 (推奨)

**Settings** → **Branches** → **Add rule** で`main`ブランチを保護：

- ✅ **Require a pull request before merging**
- ✅ **Require status checks to pass before merging**
  - 必須チェック: `Test Suite` (テストワークフローから)
- ✅ **Restrict pushes that create files**

### 3. セキュリティ設定

**SSH鍵の管理**:
- 秘密鍵は定期的にローテーション  
- VPS上の`~/.ssh/authorized_keys`の管理
- 不要な鍵の削除

**GitHub Secretsの管理**:
- 必要なもののみ設定
- 定期的な見直し
- アクセスログの監視



## 🧪 動作確認方法

### 1. テストワークフローの動作確認

1. **新しいブランチを作成**:
   ```bash
   git checkout -b feature/ci-test
   echo "# CI Test" >> README.md
   git add README.md
   git commit -m "test: CI workflow test"
   git push origin feature/ci-test
   ```

2. **Pull Requestを作成**:
   - GitHubでPRを作成
   - テンプレートに従って内容を記入

3. **ワークフローの実行確認**:
   - PRページの「Checks」タブで`Test Suite`が実行されることを確認
   - すべてのテスト（Backend, Frontend, E2E）が正常完了することを確認

### 2. Lightsailデプロイワークフローの動作確認

**前提**: GitHub Secretsが正しく設定されていること

1. **PRをマージ**:
   - テストが通ったPRをmainブランチにマージ

2. **デプロイワークフローの実行確認**:
   - Actionsタブで`🚀 Lightsail Auto Deploy`が自動実行される
   - テスト→デプロイ→ヘルスチェックの順で実行
   - ヘルスチェックが成功したらデプロイ完了

3. **アプリケーション確認**:
   ```bash
   # ブラウザまたはcurlでアクセス確認
   curl http://YOUR_SERVER_IP/health
   ```

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

#### 1. SSH接続エラー
```
Permission denied (publickey)
```

**解決方法**: 
- `LIGHTSAIL_SSH_PRIVATE_KEY`の値を確認
- VPS上の`~/.ssh/authorized_keys`にpublic keyが登録されているか確認
- SSH鍵の形式が正しいか確認

#### 2. デプロイスクリプト実行エラー
```
deploy.sh: Permission denied
```

**解決方法**: 
```bash
# VPS上で権限を確認・修正
ssh deploy@YOUR_SERVER_IP
chmod +x /var/www/YOUR_REPOSITORY/scripts/deploy.sh
```

#### 3. ヘルスチェック失敗
```
Health check failed after 5 attempts
```

**解決方法**: 
- VPS上で手動確認: `curl http://localhost/health`
- サービス状態確認: `docker compose -f docker-compose.production.yml ps`
- ログ確認: `docker compose -f docker-compose.production.yml logs`

#### 4. テスト失敗
```
Tests failed
```

**解決方法**: 
- ローカルでテストが通るか確認: `php artisan test`, `npm run test`
- 依存関係の問題: `composer install`, `npm ci`を再実行
- データベース関連: マイグレーション・シーディングの確認

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

1. **GitHub Secretsの設定** - 最重要！
2. **ブランチ保護ルールの設定**  
3. **テストPRでの動作確認**
4. **本番デプロイの実行と検証**
5. **監視・通知の追加設定**

## 📞 サポート

問題が発生した場合：

1. **GitHub Actionsのログ確認**
   - リポジトリのActionsタブ → 失敗したワークフロー → 詳細ログ確認

2. **VPS上での状況確認**
   ```bash
   # SSH接続してログ確認
   ssh deploy@YOUR_SERVER_IP
   cd /var/www/YOUR_REPOSITORY
   docker compose -f docker-compose.production.yml logs
   ```

3. **設定の再確認**
   - GitHub Secretsが正しく設定されているか
   - VPS上の`.env.production`が正しく設定されているか
   - SSH鍵の権限と形式が正しいか

詳細な設定手順は `docs/github-actions-setup.md` を参照してください。