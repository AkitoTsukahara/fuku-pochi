# Task 031: Group Controller薄化・UseCase呼び出し移行

## 概要
既存のGroup ControllerをStranglerパターンで段階的に薄化し、UseCase呼び出しに移行する。既存機能を壊さずクリーンアーキテクチャに移行。

## 目標
- Controller層の責務を最小限に限定
- UseCase・QueryServiceによるビジネスロジック実行
- レスポンス形式の互換性維持

## 作業内容

### 1. CreateGroupController移行

#### 1.1 既存Controller確認
- 現在の`CreateGroupController`の実装を分析
- レスポンス形式・エラーハンドリングの互換性確認

#### 1.2 Controller薄化実装
- `app/Http/Controllers/Api/Groups/CreateGroupController.php`
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
            
            return response()->json([
                'success' => true,
                'message' => 'グループが正常に作成されました',
                'data' => $response->toArray()
            ], Response::HTTP_CREATED);
            
        } catch (DomainException $e) {
            $httpException = $this->exceptionMapper->mapToHttpException($e);
            
            return response()->json([
                'success' => false,
                'message' => $httpException->getMessage(),
                'data' => null
            ], $httpException->getStatusCode());
        }
    }
}
```

### 2. GetGroupController移行

#### 2.1 既存実装の分析
- `GetGroupController`の現在の実装確認
- ShareTokenによる検索ロジックの移行

#### 2.2 Controller薄化実装
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
                return response()->json([
                    'success' => false,
                    'message' => '指定されたトークンのグループが見つかりません',
                    'data' => null
                ], Response::HTTP_NOT_FOUND);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'グループ情報を取得しました',
                'data' => $response->toArray()
            ], Response::HTTP_OK);
            
        } catch (DomainException $e) {
            $httpException = $this->exceptionMapper->mapToHttpException($e);
            
            return response()->json([
                'success' => false,
                'message' => $httpException->getMessage(),
                'data' => null
            ], $httpException->getStatusCode());
        }
    }
}
```

### 3. 共通レスポンス処理

#### 3.1 Controller基底クラス強化
- `app/Http/Controllers/Controller.php`に共通メソッド追加
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

### 4. FormRequest調整

#### 4.1 CreateGroupRequest調整
- 既存のバリデーションルール維持
- 必要に応じてエラーメッセージ調整

### 5. テスト移行

#### 5.1 既存テスト確認
- `tests/Feature/Groups/CreateGroupControllerTest.php`の確認
- `tests/Feature/Groups/GetGroupControllerTest.php`の確認

#### 5.2 テスト調整
```php
class CreateGroupControllerTest extends TestCase
{
    // 既存のテストケースを維持
    // レスポンス形式の互換性確認
    // UseCase経由での動作確認
    
    public function test_create_group_success()
    {
        $response = $this->postJson('/api/groups', [
            'name' => 'テストグループ'
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'グループが正常に作成されました',
                'data' => [
                    'name' => 'テストグループ',
                    // その他のフィールド確認
                ]
            ]);
    }
}
```

### 6. DI設定

#### 6.1 ServiceProvider更新
- `DomainServiceProvider`でUseCase・QueryServiceのバインド
```php
// UseCase bindings
$this->app->bind(CreateGroupUseCase::class);
$this->app->bind(GetGroupQueryService::class);

// Exception Mapper bindings  
$this->app->bind(GroupExceptionMapper::class);
```

### 7. 段階的移行戦略

#### 7.1 Feature Flag対応（オプション）
```php
class CreateGroupController extends Controller
{
    public function __invoke(CreateGroupRequest $request): JsonResponse
    {
        if (config('app.use_clean_architecture', false)) {
            return $this->executeWithUseCase($request);
        }
        
        return $this->executeLegacy($request);
    }

    private function executeWithUseCase(CreateGroupRequest $request): JsonResponse
    {
        // UseCase呼び出し実装
    }

    private function executeLegacy(CreateGroupRequest $request): JsonResponse
    {
        // 既存実装
    }
}
```

### 8. パフォーマンス検証

#### 8.1 レスポンス時間計測
- 移行前後でのパフォーマンス比較
- N+1問題等の発生チェック

#### 8.2 メモリ使用量確認
- UseCase経由でのメモリ使用量確認
- 不要なオブジェクト生成の回避

## 実装ルール

### 8.1 互換性維持
- 既存APIのレスポンス形式を完全互換
- エラーメッセージ・ステータスコードの維持
- 既存テストが全て通ること

### 8.2 段階的移行
- 1つのControllerずつ確実に移行
- Feature Flagでリスク軽減（オプション）
- 問題発生時の即座なロールバック可能性

### 8.3 エラーハンドリング
- DomainExceptionのHTTP例外マッピング
- 予期しない例外の適切な処理
- ログ出力の継続

## 受け入れ条件
- [ ] CreateGroupControllerがUseCase呼び出しに移行されている
- [ ] GetGroupControllerがQueryService呼び出しに移行されている
- [ ] 既存テストが全て通っている
- [ ] レスポンス形式の互換性が保たれている
- [ ] エラーハンドリングが適切に実装されている
- [ ] DI設定が正しく行われている
- [ ] パフォーマンスに問題がない
- [ ] Controller層が薄化されている

## 参考
- Stranglerパターンで段階的移行
- 既存機能を壊さず安全に移行
- UseCase・QueryServiceでビジネスロジック分離