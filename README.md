# ふくぽち (fuku-pochi)

保育園に預ける子どもの衣類ストック状況を、夫婦で共有しやすく・直感的に管理できるスマホWebアプリです。

## 技術スタック

- **フロントエンド**: SvelteKit
- **バックエンド**: Laravel  
- **データベース**: MySQL
- **テスト**: Vitest (フロントエンド) / PHPUnit (バックエンド)
- **デプロイ**: Laravel Vapor

## 開発環境構築手順

開発を始める手順については [dev-env.md](agent-context/dev-env.md) をご参照ください。

## ドキュメント一覧

| ドキュメント | 目的 |
|------------|------|
| [design-doc.md](agent-context/design-doc.md) | プロダクトのUI/UX・機能仕様 |
| [architecture.md](agent-context/architecture.md) | 技術アーキテクチャの方針整理 |
| [api-spec.md](agent-context/api-spec.md) | API仕様の一覧 |
| [db-schema.md](agent-context/db-schema.md) | ER図とデータモデル設計 |
| [dev-env.md](agent-context/dev-env.md) | ローカル開発環境構築手順 |
| [ci-cd.md](agent-context/ci-cd.md) | CI/CD構成と運用ルール |
| [prompt-guide.md](agent-context/prompt-guide.md) | 生成AI活用のプロンプト集 |
