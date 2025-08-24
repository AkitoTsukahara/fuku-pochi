# GitHub Actions Lightsail自動デプロイ設定ガイド

## 概要

mainブランチへのプッシュ時に、自動的にテストを実行し、成功した場合にLightsail VPSへデプロイするGitHub Actionsワークフローです。

## 📋 事前準備

### 1. Lightsail VPS準備
- VPSが起動していること
- SSH鍵認証が設定済みであること  
- `deploy`ユーザーが作成されていること
- Dockerとdocker-composeがインストール済みであること

### 2. プロジェクトのVPSデプロイ完了
- 手動デプロイが成功していること
- `.env.production`ファイルが正しく設定されていること

## 🔐 GitHub Secrets設定

### 必要なSecrets

以下のSecretsをGitHubリポジトリに設定してください：

#### 1. `LIGHTSAIL_SSH_PRIVATE_KEY`
**説明**: VPSにSSH接続するための秘密鍵

**取得方法**:
```bash
# ローカルで秘密鍵の内容を表示
cat ~/.ssh/id_rsa

# または、VPSセットアップ時に生成したキーファイル
cat /path/to/your/private/key
```

**設定値**: 秘密鍵全体（`-----BEGIN OPENSSH PRIVATE KEY-----`から`-----END OPENSSH PRIVATE KEY-----`まで）

#### 2. `LIGHTSAIL_SERVER_IP`
**説明**: LightsailインスタンスのIPアドレス

**取得方法**:
- AWS Lightsailコンソールで確認
- または、VPSにログインして`curl ifconfig.me`で確認

**設定値例**: `54.178.217.122`

#### 3. ~~`REPOSITORY_NAME`~~ (不要)
**説明**: ~~VPS上のプロジェクトディレクトリ名~~

**✅ 自動化済み**: GitHubリポジトリ名から自動判定されるため設定不要

### GitHub Secrets設定手順

1. **GitHubリポジトリページを開く**
2. **Settings** タブをクリック
3. **左サイドバー** → **Secrets and variables** → **Actions**
4. **New repository secret** をクリック
5. **Name**と**Value**を入力して**Add secret**

## 🚀 ワークフロー動作内容

### 自動実行トリガー
- `main`ブランチへの`push`
- 手動実行（`workflow_dispatch`）

### 実行フロー

#### 1. テストフェーズ 🧪
- **環境**: Ubuntu Latest + MySQL 8.4 + Redis 7 
- **PHP**: 8.4 + 必要な拡張モジュール
- **Node.js**: 20
- **実行内容**:
  - Composerの依存関係インストール
  - Laravelアプリケーションキー生成
  - データベースマイグレーション・シーディング
  - PHPUnitテスト実行
  - フロントエンドテスト（Vitest）実行
  - E2Eテスト（Playwright）実行

#### 2. デプロイフェーズ 🚢
**テスト成功時のみ実行**

- **SSH接続**: VPSに接続
- **デプロイ実行**: `./scripts/deploy.sh`を実行
- **ヘルスチェック**: アプリケーションの動作確認
- **結果報告**: 成功/失敗の報告

## 📊 デプロイ後の確認

### 自動ヘルスチェック
ワークフローは以下をチェックします：
- `http://YOUR_SERVER_IP/health` (ステータス200)
- 5回リトライ（10秒間隔）
- 失敗時はワークフロー失敗

### 手動確認方法
```bash
# アプリケーション動作確認
curl http://YOUR_SERVER_IP/health

# APIエンドポイント確認  
curl http://YOUR_SERVER_IP/api/health

# サービス状態確認
ssh deploy@YOUR_SERVER_IP
cd /var/www/YOUR_REPOSITORY
docker compose -f docker-compose.production.yml ps
```

## 🔧 トラブルシューティング

### よくある問題と対処法

#### 1. SSH接続エラー
**エラー**: `Permission denied (publickey)`

**対処法**:
- `LIGHTSAIL_SSH_PRIVATE_KEY`の値が正しいか確認
- 改行コードが正しく含まれているか確認
- VPS側でdeployユーザーの`~/.ssh/authorized_keys`を確認

#### 2. テスト失敗
**エラー**: `Tests failed`

**対処法**:
- ローカルでテストが通るか確認: `npm run test` / `php artisan test`
- データベース設定やシーディングの問題を確認
- Actions内のログを詳細確認

#### 3. デプロイスクリプト実行エラー
**エラー**: `deploy.sh: command not found` or `Permission denied`

**対処法**:
```bash
# VPSで権限確認・修正
ssh deploy@YOUR_SERVER_IP
cd /var/www/YOUR_REPOSITORY
chmod +x ./scripts/deploy.sh
ls -la ./scripts/deploy.sh
```

#### 4. ヘルスチェック失敗
**エラー**: `Health check failed`

**対処法**:
- VPSで手動ヘルスチェック: `curl http://localhost/health`
- サービス状態確認: `docker compose -f docker-compose.production.yml ps`
- ログ確認: `docker compose -f docker-compose.production.yml logs`

### ログ確認方法

#### GitHub Actions実行ログ
1. GitHubリポジトリ → **Actions**タブ
2. 失敗したワークフロー実行をクリック
3. 各ジョブの詳細ログを確認

#### VPS側のログ確認
```bash
# アプリケーションログ
ssh deploy@YOUR_SERVER_IP
cd /var/www/YOUR_REPOSITORY

# Docker Compose各サービスのログ
docker compose -f docker-compose.production.yml logs nginx
docker compose -f docker-compose.production.yml logs backend  
docker compose -f docker-compose.production.yml logs frontend
docker compose -f docker-compose.production.yml logs database
```

## 🔄 CI/CDの有効化・無効化

### 一時的な無効化
特定のコミットでCI/CDをスキップしたい場合：

```bash
git commit -m "fix: urgent hotfix [skip ci]"
```

### 永続的な無効化
`.github/workflows/lightsail-deploy.yml`の`on:`セクションをコメントアウト：

```yaml
# on:
#   push:
#     branches: [ main ]
on:
  workflow_dispatch:  # 手動実行のみ
```

## 📈 運用ベストプラクティス

### 1. ブランチ戦略
- `main`ブランチは常にデプロイ可能な状態を保つ
- フィーチャーブランチでの開発→PR→レビュー→mainマージ
- ブランチ保護ルール設定推奨

### 2. ログ監視
- デプロイ後は必ずアプリケーションの動作確認
- エラーログの定期確認
- ディスク容量の監視

### 3. セキュリティ
- SSH鍵の定期ローテーション
- 環境変数の定期見直し
- VPSのセキュリティアップデート

## 📞 サポート

### 緊急時の対処
1. **即座の対応**: GitHub Actionsワークフローの無効化
2. **手動デプロイ**: VPSで`git checkout`して前のバージョンに戻す
3. **問題調査**: ログ確認とイシューの特定

### 設定変更時の注意
- 新しい環境変数追加時は`.env.production.example`も更新
- SSH鍵変更時は必ず事前テスト
- デプロイスクリプト変更時は段階的にテスト