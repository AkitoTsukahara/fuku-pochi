# ふくぽち (fuku-pochi)

保育園に預ける子どもの衣類ストック状況を、夫婦で共有しやすく・直感的に管理できるスマホWebアプリです。

## 技術スタック

- **フロントエンド**: SvelteKit (Node.js 22 LTS)
- **バックエンド**: Laravel 12.x (PHP 8.4)
- **データベース**: MySQL 8.4 LTS
- **テスト**: Vitest (フロントエンド) / PHPUnit (バックエンド)
- **デプロイ**: Laravel Vapor
- **コンテナ**: Docker & Docker Compose

## 開発環境構築手順

### 必要な環境
- Docker & Docker Compose
- Git
- [Task](https://taskfile.dev/) (タスクランナー)

### Taskのインストール
```bash
# macOS (Homebrew)
brew install go-task

# その他のOSはこちら: https://taskfile.dev/installation/
```

### クイックスタート

1. **リポジトリのクローンと起動**
```bash
git clone https://github.com/username/fuku-pochi.git
cd fuku-pochi

# 初期セットアップ & 起動（Taskfileを使用）
task up
```

2. **アクセス確認**
- フロントエンド: http://localhost:5173
- バックエンドAPI: http://localhost:8000
- Mailpit (メール確認): http://localhost:8025
- Redis: localhost:6379

### 開発用コマンド（Taskfile）
```bash
# 利用可能なタスク一覧
task --list

# Docker環境起動
task up

# Docker環境停止
task down

# ログ確認
task logs

# 再構築
task rebuild

# 開発環境起動（ログ表示付き）
task dev

# シェルアクセス
task shell-backend   # Laravel
task shell-frontend  # SvelteKit
task shell-redis     # Redis CLI

# Laravel関連
task migrate         # マイグレーション実行
task seed           # シーダー実行
task test-backend   # テスト実行
```

詳細な手順については [dev-env.md](agent-context/dev-env.md) をご参照ください。

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
