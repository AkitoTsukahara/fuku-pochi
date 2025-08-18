# GitHub Actions CI/CD設定

## 概要
自動化されたテスト実行とデプロイメントパイプラインをGitHub Actionsで構築する

## 作業内容
- [x] テスト実行ワークフロー(.github/workflows/test.yml)の作成
- [x] デプロイワークフロー(.github/workflows/deploy.yml)の作成
- [x] プルリクエストテンプレートの作成
- [x] CI/CDセットアップドキュメントの作成
- [x] ブランチ保護ルールの設定手順書作成
- [x] シークレット環境変数の設定手順書作成

## 完了条件
- [x] PRに対して自動的にテストが実行される設定完了
- [x] mainブランチへのマージ時に自動デプロイが実行される設定完了
- [x] テストが失敗した場合はマージがブロックされる設定完了
- [x] ブランチ保護ルール設定手順が文書化されている
- [x] 必要なシークレット設定手順が文書化されている
- [x] 動作確認方法が明記されている

## 関連ファイル
- .github/workflows/test.yml
- .github/workflows/deploy.yml
- .github/pull_request_template.md
- .github/README.md

## 実装内容

### テストワークフロー
- PHP 8.2、Node.js 20、MySQL 8.0を使用
- バックエンド（PHPUnit）・フロントエンド（Vitest）・E2E（Playwright）テスト実行
- 静的解析（ESLint、TypeScript型チェック）の実行
- カバレッジレポートの生成（Codecov対応）
- actions/checkout@v4、actions/setup-node@v4の最新版を使用

### デプロイワークフロー
- バックエンドとフロントエンドの並列ビルド・デプロイ
- 複数デプロイ方法のプレースホルダー（AWS、Vercel、Netlify等）
- 通知機能のプレースホルダー（Slack、Discord）

### セットアップ手順書
- ブランチ保護ルール設定の詳細手順
- GitHub Secrets設定の詳細手順  
- 動作確認方法の詳細説明
- トラブルシューティングガイド

## 実装時の特別対応

**⚠️ CI/CDワークフローは現在無効化されています**

ユーザーの要望により、CI/CDを即座に使用しない前提で以下の対応を実施：

- `test.yml` と `deploy.yml` のトリガーを `workflow_dispatch`（マニュアル実行）のみに変更
- 自動実行（push/pull_request）をコメントアウト
- README.md に無効化中である旨と有効化手順を明記

これにより、GitHub にプッシュしても自動でワークフローが実行されず、失敗することがありません。

## 将来の有効化手順（ユーザー作業）
1. 各ワークフローファイルで `on:` セクションのコメントアウトを解除
2. `.github/README.md` を参照してブランチ保護ルールを設定
3. 使用するデプロイ方法に応じてGitHub Secretsを設定
4. `deploy.yml` で不要なデプロイ方法をコメントアウト
5. 動作確認方法に従ってテスト実行