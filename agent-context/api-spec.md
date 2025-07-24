# ふくぽち - API仕様

## REST設計原則

- RESTful APIとして設計
- HTTPメソッドの適切な使用（GET, POST, PUT, DELETE）
- リソース指向のURL設計
- JSON形式でのリクエスト・レスポンス
- 適切なHTTPステータスコード使用

## エンドポイント一覧

### 認証・共有関連

| エンドポイント | メソッド | 説明 |
|---------------|----------|------|
| `/api/groups` | POST | 新しいグループを作成 |
| `/api/groups/{token}` | GET | トークンでグループ情報を取得 |

### 子ども管理

| エンドポイント | メソッド | 説明 |
|---------------|----------|------|
| `/api/groups/{token}/children` | GET | グループの子ども一覧を取得 |
| `/api/groups/{token}/children` | POST | 新しい子どもを追加 |
| `/api/children/{id}` | PUT | 子ども情報を更新 |
| `/api/children/{id}` | DELETE | 子どもを削除 |

### ストック管理

| エンドポイント | メソッド | 説明 |
|---------------|----------|------|
| `/api/children/{id}/stock` | GET | 子どものストック一覧を取得 |
| `/api/children/{id}/stock-increment` | POST | ストック数を増加 |
| `/api/children/{id}/stock-decrement` | POST | ストック数を減少 |

### 衣類カテゴリ

| エンドポイント | メソッド | 説明 |
|---------------|----------|------|
| `/api/clothing-categories` | GET | 衣類カテゴリ一覧を取得 |

## エンドポイント詳細

### POST /api/groups
新しいグループを作成

**リクエスト:**
```json
{
  "name": "山田家"
}
```

**レスポンス (201):**
```json
{
  "id": 1,
  "name": "山田家",
  "share_token": "abc123def456",
  "created_at": "2024-01-01T10:00:00Z"
}
```

### GET /api/groups/{token}
トークンでグループ情報を取得

**パラメータ:**
- `token`: string - 共有トークン

**レスポンス (200):**
```json
{
  "id": 1,
  "name": "山田家",
  "share_token": "abc123def456",
  "children": [
    {
      "id": 1,
      "name": "太郎",
      "created_at": "2024-01-01T10:00:00Z"
    }
  ]
}
```

### POST /api/children/{id}/stock-increment
ストック数を増加

**パラメータ:**
- `id`: integer - 子どもID

**リクエスト:**
```json
{
  "clothing_category_id": 1,
  "increment": 1
}
```

**レスポンス (200):**
```json
{
  "id": 1,
  "child_id": 1,
  "clothing_category_id": 1,
  "current_count": 3,
  "updated_at": "2024-01-01T10:00:00Z"
}
```

### POST /api/children/{id}/stock-decrement
ストック数を減少

**パラメータ:**
- `id`: integer - 子どもID

**リクエスト:**
```json
{
  "clothing_category_id": 1,
  "decrement": 1
}
```

**レスポンス (200):**
```json
{
  "id": 1,
  "child_id": 1,
  "clothing_category_id": 1,
  "current_count": 2,
  "updated_at": "2024-01-01T10:00:00Z"
}
```

### GET /api/children/{id}/stock
子どものストック一覧を取得

**パラメータ:**
- `id`: integer - 子どもID

**レスポンス (200):**
```json
[
  {
    "id": 1,
    "child_id": 1,
    "clothing_category": {
      "id": 1,
      "name": "Tシャツ",
      "icon_path": "/icons/tshirt.svg"
    },
    "current_count": 2
  },
  {
    "id": 2,
    "child_id": 1,
    "clothing_category": {
      "id": 2,
      "name": "ズボン",
      "icon_path": "/icons/pants.svg"
    },
    "current_count": 1
  }
]
```

## 想定するステータスコード

| コード | 意味 | 使用場面 |
|--------|------|----------|
| 200 | OK | 正常に処理完了 |
| 201 | Created | リソース作成成功 |
| 400 | Bad Request | リクエストパラメータ不正 |
| 404 | Not Found | リソースが存在しない |
| 422 | Unprocessable Entity | バリデーションエラー |
| 500 | Internal Server Error | サーバー内部エラー |

## 検証ルール（テスト対象範囲）

### リクエストバリデーション
- グループ名: 必須、255文字以内
- 子ども名: 必須、255文字以内
- clothing_category_id: 必須、存在するカテゴリID
- increment/decrement: 必須、1以上の整数
- current_count: 0以上の整数（負の値不許可）

### ビジネスルール
- ストック数は0未満にならない
- 同一子ども・同一カテゴリの重複データは作成されない
- 無効なトークンでのアクセスは拒否される

### テスト観点
- 正常系テスト（成功ケース）
- 異常系テスト（バリデーションエラー、存在しないリソース）
- 境界値テスト（ストック数0、最大値）
- セキュリティテスト（不正なトークン、SQL インジェクション対策）