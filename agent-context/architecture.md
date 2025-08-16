# ふくぽち - 技術アーキテクチャ設計

## 技術スタック

| 項目 | 内容 |
|------|------|
| フロントエンド | SvelteKit（Node.js 22 LTS） |
| バックエンド | Laravel 12.x（PHP 8.4） |
| データベース | MySQL 8.4 LTS |
| テスト | Vitest（FE）・PHPUnit（BE）・包括的Featureテスト |
| デプロイ候補 | Laravel Vapor（無料プラン前提で調査中） |

## Laravel側の構成

### DDD + クリーンアーキテクチャの粒度

- **Domain層**: エンティティとビジネスロジック（UserGroup, Children, StockItems）
- **Application層**: ユースケース実装
- **Infrastructure層**: データベース、外部API、ファイルシステム
- **Presentation層**: コントローラとビュー（API応答）

### コントローラ設計方針 - 単一アクションコントローラ

- **単一責任の原則**: 各コントローラは1つのAPIエンドポイントのみを担当
- `__invoke()` メソッド単位での実装
- リクエスト → バリデーション → ビジネスロジック実行 → レスポンス の流れ
- 例: `CreateGroupController`, `GetChildrenController`, `UpdateChildController`

### ディレクトリ構造（コントローラ）
```
app/Http/Controllers/Api/
├── Groups/
│   ├── CreateGroupController.php
│   └── GetGroupController.php
├── Children/
│   ├── GetChildrenController.php
│   ├── CreateChildController.php
│   ├── UpdateChildController.php
│   └── DeleteChildController.php
└── Stock/（今後実装予定）
    ├── GetStockController.php
    ├── IncrementStockController.php
    └── DecrementStockController.php
```

## SvelteKit側の構成方針

```
src/
├── routes/          # ページコンポーネント
├── lib/
│   ├── components/  # 再利用可能なコンポーネント
│   ├── stores/      # 状態管理（Svelte stores）
│   ├── api/         # API通信ロジック
│   └── utils/       # ユーティリティ関数
└── app.html         # HTMLテンプレート
```

### 状態管理

- Svelte stores を使用
- ストック数の状態管理
- ユーザーグループ・子どもデータの管理

## ファイル構成例

```
project/
├── backend/         # Laravel プロジェクト
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/Api/  # 単一アクションコントローラ
│   │   │   └── Requests/         # FormRequestクラス
│   │   ├── Models/        # Eloquentモデル（HasFactory使用）
│   │   └── Services/      # ビジネスロジック
│   ├── database/
│   │   ├── migrations/
│   │   └── factories/     # モデルファクトリー
│   └── tests/
│       ├── Feature/       # APIエンドポイントテスト
│       │   ├── Groups/
│       │   └── Children/
│       └── Unit/          # ユニットテスト
├── frontend/        # SvelteKit プロジェクト
│   ├── src/
│   │   ├── routes/
│   │   └── lib/
│   └── tests/
└── docs/           # プロジェクトドキュメント
```

## データフロー

```
SvelteKit Frontend
    ↓ HTTP Request
Laravel API
    ↓ Action Class
Business Logic
    ↓ Eloquent ORM
MySQL Database
```

## 保守性・責務分離への意識点

1. **単一責任の原則**: 各クラス・関数は一つの責任のみを持つ
2. **依存性の逆転**: 抽象に依存し、具象に依存しない
3. **疎結合**: モジュール間の依存を最小限に抑える
4. **テスタビリティ**: ユニットテスト・Featureテストが書きやすい構造（各エンドポイントに専用テスト）
5. **可読性**: コードの意図が明確で、他の開発者が理解しやすい

## セキュリティ考慮事項

- CSRF保護の実装
- SQL インジェクション対策（Eloquent ORM使用）
- XSS対策（適切なエスケープ処理）
- 認証・認可の仕組み（将来的にSNSログイン対応）