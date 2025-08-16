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

## クリーンアーキテクチャでのController実装（2025年リファクタリング後）

### Controller薄化の原則

**Controller責務の限定:**
1. HTTPリクエスト/レスポンス処理
2. UseCase/QueryService呼び出し
3. 例外ハンドリング・エラーマッピング
4. 認証・認可

**Controller禁止事項:**
- ビジネスロジックの直接実装
- Eloquentモデルの直接操作
- データベースクエリの実行
- 複雑なデータ変換処理

### 薄化されたController実装パターン

#### 基本構造（UseCase呼び出し）
```php
class CreateGroupController extends Controller
{
    public function __construct(
        private readonly CreateGroupUseCase $createGroupUseCase,
        private readonly GroupExceptionMapper $exceptionMapper
    ) {}

    public function __invoke(CreateGroupRequest $request): JsonResponse
    {
        try {
            $command = CreateGroupCommand::fromRequest($request->validated());
            $response = $this->createGroupUseCase->execute($command);
            
            return $this->successResponse(
                'グループが正常に作成されました',
                $response,
                Response::HTTP_CREATED
            );
            
        } catch (DomainException $e) {
            return $this->handleDomainException($e, $this->exceptionMapper);
        }
    }
}
```

#### 参照系Controller（QueryService呼び出し）
```php
class GetGroupController extends Controller
{
    public function __construct(
        private readonly GetGroupQueryService $getGroupQueryService,
        private readonly GroupExceptionMapper $exceptionMapper
    ) {}

    public function __invoke(string $token): JsonResponse
    {
        try {
            $query = GetGroupByTokenQuery::fromRequest($token);
            $response = $this->getGroupQueryService->getByShareToken($query);
            
            if (!$response) {
                return $this->errorResponse(
                    '指定されたトークンのグループが見つかりません',
                    Response::HTTP_NOT_FOUND
                );
            }
            
            return $this->successResponse(
                'グループ情報を取得しました',
                $response
            );
            
        } catch (DomainException $e) {
            return $this->handleDomainException($e, $this->exceptionMapper);
        }
    }
}
```

### Controller基底クラス強化

#### 共通メソッド追加
```php
abstract class Controller extends BaseController
{
    protected function successResponse(
        string $message,
        array|ResponseDTO $data,
        int $statusCode = Response::HTTP_OK
    ): JsonResponse {
        $responseData = $data instanceof ResponseDTO ? $data->toArray() : $data;
        
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $responseData
        ], $statusCode);
    }

    protected function errorResponse(
        string $message,
        int $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR,
        mixed $data = null
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    protected function handleDomainException(
        DomainException $exception,
        ExceptionMapperInterface $mapper
    ): JsonResponse {
        $httpException = $mapper->mapToHttpException($exception);
        
        return $this->errorResponse(
            $httpException->getMessage(),
            $httpException->getStatusCode()
        );
    }
}
```

### レスポンス形式統一（継続）

#### 成功レスポンス
```json
{
  "success": true,
  "message": "操作が正常に完了しました",
  "data": {
    "id": "group-123",
    "name": "テストグループ"
  }
}
```

#### エラーレスポンス（強化版）
```json
{
  "success": false,
  "message": "ユーザーフレンドリーなメッセージ",
  "error_code": "BUSINESS_RULE_VIOLATION",
  "trace_id": "trace_674a1b2c3d4e5f",
  "data": null
}
```

### 実装指針（更新版）
1. **UseCase/QueryService経由**でのビジネスロジック実行
2. **例外マッピング**による統一的なエラーハンドリング
3. **DTO変換**でリクエスト/レスポンス処理
4. **レスポンス形式**の互換性維持
5. **trace_id**による追跡可能性確保
6. **構造化ログ**による運用性向上

### 段階的移行戦略

#### Phase 1: UseCase/QueryService実装
1. Domain層・Application層の実装
2. Infrastructure層でRepository実装

#### Phase 2: Controller薄化
1. 既存Controllerの動作確認
2. UseCase/QueryService呼び出しへの置き換え
3. レスポンス形式の互換性確認

#### Phase 3: テスト修正
1. 既存テストの動作確認
2. 新しいアーキテクチャでのテスト追加
3. 統合テスト・契約テストの実装

### 注意事項（更新版）
- Controller層は**最小限の責務**のみ担当
- **UseCase/QueryService**経由でビジネスロジックを実行
- **例外マッピング**で統一的なエラーハンドリング
- **既存レスポンス形式**の完全互換性を維持
- **trace_id**による問題追跡可能性を確保