# Controller実装ガイドライン

## 単一アクションコントローラ（Single Action Controller）

### 概要
このプロジェクトでは、単一責任の原則（SRP）に従い、各APIエンドポイントを独立した単一アクションコントローラとして実装します。

### 採用理由
1. **単一責任の原則**: 各コントローラは1つのアクションのみを担当
2. **可読性向上**: 各ファイルの責務が明確
3. **テスタビリティ**: 個別のテストが容易
4. **保守性**: 変更の影響範囲が限定的

### ディレクトリ構造
```
app/Http/Controllers/Api/
├── Groups/
│   ├── CreateGroupController.php
│   └── GetGroupController.php
└── Children/
    ├── GetChildrenController.php
    ├── CreateChildController.php
    ├── UpdateChildController.php
    └── DeleteChildController.php
```

### 実装パターン

#### 基本構造
```php
<?php

namespace App\Http\Controllers\Api\Groups;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateGroupRequest;
use App\Models\UserGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class CreateGroupController extends Controller
{
    /**
     * Store a newly created user group.
     */
    public function __invoke(CreateGroupRequest $request): JsonResponse
    {
        // 実装内容
    }
}
```

#### ルート定義
```php
// 単一アクションコントローラの場合は、クラス名のみを指定
Route::post('/groups', CreateGroupController::class);
Route::get('/groups/{token}', GetGroupController::class);
Route::get('/groups/{token}/children', GetChildrenController::class);
Route::post('/groups/{token}/children', CreateChildController::class);
Route::put('/children/{id}', UpdateChildController::class);
Route::delete('/children/{id}', DeleteChildController::class);
```

### レスポンス形式

#### 成功レスポンス
```php
return response()->json([
    'success' => true,
    'message' => '操作が正常に完了しました',
    'data' => $data
], Response::HTTP_OK); // または HTTP_CREATED
```

#### エラーレスポンス
```php
return response()->json([
    'success' => false,
    'message' => 'エラーメッセージ',
    'data' => null
], Response::HTTP_NOT_FOUND); // または適切なHTTPステータス
```

### コントローラ命名規則

#### パターン
- **Create**: `Create{Resource}Controller` (POST)
- **Get/Show**: `Get{Resource}Controller` or `Get{Resources}Controller` (GET)
- **Update**: `Update{Resource}Controller` (PUT/PATCH)
- **Delete**: `Delete{Resource}Controller` (DELETE)

#### 例
- `CreateGroupController` - グループ作成
- `GetGroupController` - 単一グループ取得
- `GetChildrenController` - 子どもリスト取得
- `CreateChildController` - 子ども作成
- `UpdateChildController` - 子ども更新
- `DeleteChildController` - 子ども削除

### バリデーション

#### FormRequest使用
```php
use App\Http\Requests\CreateGroupRequest;

public function __invoke(CreateGroupRequest $request): JsonResponse
{
    $validated = $request->validated();
    // 実装
}
```

#### リクエストクラス
```php
class CreateGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'グループ名は必須です',
            'name.string' => 'グループ名は文字列で入力してください',
            'name.max' => 'グループ名は255文字以内で入力してください',
        ];
    }
}
```

### エラーハンドリング

#### 存在しないリソース
```php
$resource = Model::find($id);

if (!$resource) {
    return response()->json([
        'success' => false,
        'message' => '指定されたリソースが見つかりません',
        'data' => null
    ], Response::HTTP_NOT_FOUND);
}
```

#### トークンベースの認証
```php
$userGroup = UserGroup::where('share_token', $token)->first();

if (!$userGroup) {
    return response()->json([
        'success' => false,
        'message' => '指定されたトークンのグループが見つかりません',
        'data' => null
    ], Response::HTTP_NOT_FOUND);
}
```

### 今後の実装指針
1. **全ての新しいAPIエンドポイント**は単一アクションコントローラとして実装
2. **既存のマルチアクションコントローラ**は段階的に単一アクションコントローラに分割
3. **ディレクトリ構造**は機能別に整理（Groups/, Children/, Stock/, etc.）
4. **レスポンス形式**は一貫性を保つ
5. **適切なHTTPステータスコード**を使用
6. **日本語エラーメッセージ**でユーザビリティを向上

### 注意事項
- `__invoke()` メソッドを使用して単一アクションを実装
- 適切な型宣言（引数・戻り値）を行う
- FormRequestクラスでバリデーションを分離
- レスポンス形式の統一を維持
- エラーハンドリングの一貫性を保つ