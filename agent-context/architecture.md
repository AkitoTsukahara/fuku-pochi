# ふくぽち - 技術アーキテクチャ設計

## 技術スタック

| 項目 | 内容 |
|------|------|
| フロントエンド | SvelteKit（Node.js 22 LTS） |
| バックエンド | Laravel 12.x（PHP 8.4） |
| データベース | MySQL 8.4 LTS |
| テスト | Vitest（FE）・PHPUnit（BE） |
| デプロイ候補 | Laravel Vapor（無料プラン前提で調査中） |

## Laravel側の構成

### DDD + クリーンアーキテクチャの粒度

- **Domain層**: エンティティとビジネスロジック（UserGroup, Children, StockItems）
- **Application層**: ユースケース実装
- **Infrastructure層**: データベース、外部API、ファイルシステム
- **Presentation層**: コントローラとビュー（API応答）

### Actionクラス設計方針

- `__invoke()` メソッド単位での単一責任
- リクエスト → バリデーション → ユースケース実行 → レスポンス の流れ
- 例: `IncrementStockAction`, `DecrementStockAction`, `GetChildrenStockAction`

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
│   │   ├── Actions/       # アクションクラス
│   │   ├── Models/        # Eloquentモデル
│   │   ├── Http/
│   │   │   └── Controllers/
│   │   └── Services/      # ビジネスロジック
│   ├── database/
│   │   └── migrations/
│   └── tests/
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
4. **テスタビリティ**: ユニットテスト・統合テストが書きやすい構造
5. **可読性**: コードの意図が明確で、他の開発者が理解しやすい

## セキュリティ考慮事項

- CSRF保護の実装
- SQL インジェクション対策（Eloquent ORM使用）
- XSS対策（適切なエスケープ処理）
- 認証・認可の仕組み（将来的にSNSログイン対応）