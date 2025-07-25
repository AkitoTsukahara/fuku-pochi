# GitHub Actions CI/CD設定

## 概要
自動化されたテスト実行とデプロイメントパイプラインをGitHub Actionsで構築する

## 作業内容
- [ ] テスト実行ワークフロー(.github/workflows/test.yml)の作成
- [ ] デプロイワークフロー(.github/workflows/deploy.yml)の作成
- [ ] プルリクエストテンプレートの作成
- [ ] ブランチ保護ルールの設定
- [ ] シークレット環境変数の設定

## 完了条件
- PRに対して自動的にテストが実行される
- mainブランチへのマージ時に自動デプロイが実行される
- テストが失敗した場合はマージがブロックされる
- 適切なブランチ保護ルールが設定されている
- 必要なシークレットが設定されている

## 関連ファイル
- .github/workflows/test.yml
- .github/workflows/deploy.yml
- .github/pull_request_template.md

## 備考
- PHP 8.4、Node.js 22 LTS、MySQL 8.4 LTSを使用
- バックエンド（PHPUnit）・フロントエンド（Vitest）両方のテスト実行
- 最低1人のコードレビュー承認を必須とする
- カバレッジレポートの生成
- 静的解析（PHPStan, ESLint）の実行
- actions/checkout@v4、actions/setup-node@v4の最新版を使用