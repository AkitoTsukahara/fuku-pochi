# Task 032: Children Controller薄化・UseCase呼び出し移行

## 概要
既存のChildren関連Controllerを段階的にUseCase・QueryService呼び出しに移行し、複雑な業務ロジックをApplication層に移す。

## 目標
- 4つのChildren Controller全ての移行
- Token→GroupId解決ロジックの統一
- 名前重複チェック等のビジネスルールの移行

## 作業内容

### 1. CreateChildController移行

#### 1.1 既存実装分析
- 現在の`CreateChildController`のトークンベース処理確認
- UserGroup検索・子ども作成ロジックの分析

#### 1.2 Controller薄化実装
```php
class CreateChildController extends Controller
{
    public function __construct(
        private readonly CreateChildUseCase $createChildUseCase,
        private readonly GetGroupQueryService $getGroupQueryService,
        private readonly ChildrenExceptionMapper $exceptionMapper
    ) {}

    public function __invoke(CreateChildRequest $request, string $token): JsonResponse
    {
        try {
            // Token→GroupId解決
            $groupQuery = GetGroupByTokenQuery::fromRequest($token);
            $groupResponse = $this->getGroupQueryService->getByShareToken($groupQuery);
            
            if (!$groupResponse) {
                return $this->errorResponse(
                    '指定されたトークンのグループが見つかりません',
                    Response::HTTP_NOT_FOUND
                );
            }

            // Child作成
            $command = CreateChildCommand::fromTokenAndData(
                new GroupId($groupResponse->groupId),
                $request->validated()
            );
            $response = $this->createChildUseCase->execute($command);
            
            return $this->successResponse(
                '子どもが正常に登録されました',
                $response,
                Response::HTTP_CREATED
            );
            
        } catch (DomainException $e) {
            return $this->handleDomainException($e, $this->exceptionMapper);
        }
    }
}
```

### 2. GetChildrenController移行

#### 2.1 Controller薄化実装
```php
class GetChildrenController extends Controller
{
    public function __construct(
        private readonly GetChildrenQueryService $getChildrenQueryService,
        private readonly ChildrenExceptionMapper $exceptionMapper
    ) {}

    public function __invoke(string $token): JsonResponse
    {
        try {
            $query = GetChildrenByGroupTokenQuery::fromRequest($token);
            $response = $this->getChildrenQueryService->getByGroupToken($query);
            
            return $this->successResponse(
                '子どもリストを取得しました',
                $response
            );
            
        } catch (DomainException $e) {
            return $this->handleDomainException($e, $this->exceptionMapper);
        }
    }
}
```

### 3. UpdateChildController移行

#### 3.1 既存実装分析
- 子ども存在チェック・名前更新ロジック確認
- バリデーション・レスポンス形式の確認

#### 3.2 Controller薄化実装
```php
class UpdateChildController extends Controller
{
    public function __construct(
        private readonly UpdateChildUseCase $updateChildUseCase,
        private readonly ChildrenExceptionMapper $exceptionMapper
    ) {}

    public function __invoke(UpdateChildRequest $request, int $id): JsonResponse
    {
        try {
            $command = UpdateChildCommand::fromRequest((string)$id, $request->validated());
            $response = $this->updateChildUseCase->execute($command);
            
            return $this->successResponse(
                '子どもの情報が正常に更新されました',
                $response
            );
            
        } catch (DomainException $e) {
            return $this->handleDomainException($e, $this->exceptionMapper);
        }
    }
}
```

### 4. DeleteChildController移行

#### 4.1 削除制約チェック追加
- 在庫データとの関連チェック
- 削除可能性の事前検証

#### 4.2 Controller薄化実装
```php
class DeleteChildController extends Controller
{
    public function __construct(
        private readonly DeleteChildUseCase $deleteChildUseCase,
        private readonly ChildrenExceptionMapper $exceptionMapper
    ) {}

    public function __invoke(int $id): JsonResponse
    {
        try {
            $command = DeleteChildCommand::fromRequest((string)$id);
            $response = $this->deleteChildUseCase->execute($command);
            
            return $this->successResponse(
                '子どもが正常に削除されました',
                $response
            );
            
        } catch (DomainException $e) {
            return $this->handleDomainException($e, $this->exceptionMapper);
        }
    }
}
```

### 5. FormRequest調整

#### 5.1 CreateChildRequest調整
- 既存バリデーションルール維持
- DomainServiceでの重複チェックとの協調

#### 5.2 UpdateChildRequest調整
- 名前変更バリデーション
- 必要に応じてカスタムルール追加

### 6. 共通ヘルパー作成

#### 6.1 TokenResolver
```php
class TokenResolver
{
    public function __construct(
        private readonly GetGroupQueryService $getGroupQueryService
    ) {}

    public function resolveGroupId(string $token): GroupId
    {
        $query = GetGroupByTokenQuery::fromRequest($token);
        $response = $this->getGroupQueryService->getByShareToken($query);
        
        if (!$response) {
            throw new GroupNotFoundException();
        }
        
        return new GroupId($response->groupId);
    }
}
```

### 7. テスト移行・調整

#### 7.1 CreateChildControllerTest調整
```php
class CreateChildControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_child_success()
    {
        // UserGroupとshare_tokenのセットアップ
        $userGroup = UserGroup::factory()->create();
        
        $response = $this->postJson("/api/groups/{$userGroup->share_token}/children", [
            'name' => 'テスト太郎'
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => '子どもが正常に登録されました',
                'data' => [
                    'name' => 'テスト太郎',
                    'user_group_id' => $userGroup->id,
                ]
            ]);
    }

    public function test_create_child_invalid_token()
    {
        $response = $this->postJson('/api/groups/invalid-token/children', [
            'name' => 'テスト太郎'
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => '指定されたトークンのグループが見つかりません'
            ]);
    }

    public function test_create_child_duplicate_name()
    {
        // 重複名前チェックのテスト
        $userGroup = UserGroup::factory()->create();
        Children::factory()->create([
            'user_group_id' => $userGroup->id,
            'name' => '重複太郎'
        ]);
        
        $response = $this->postJson("/api/groups/{$userGroup->share_token}/children", [
            'name' => '重複太郎'
        ]);

        $response->assertStatus(409); // Conflict
    }
}
```

#### 7.2 その他テスト調整
- GetChildrenControllerTest
- UpdateChildControllerTest  
- DeleteChildControllerTest

### 8. エラーハンドリング強化

#### 8.1 統一エラーレスポンス
```php
class ChildrenExceptionMapper
{
    public function mapToHttpException(DomainException $exception): HttpException
    {
        return match ($exception::class) {
            ChildNotFoundException::class => new NotFoundHttpException('指定された子どもが見つかりません'),
            GroupNotFoundException::class => new NotFoundHttpException('指定されたトークンのグループが見つかりません'),
            DuplicateChildNameException::class => new ConflictHttpException('同じ名前の子どもが既に存在します'),
            CannotDeleteChildException::class => new ConflictHttpException('この子どもは削除できません。関連データが存在します'),
            InvalidValueException::class => new UnprocessableEntityHttpException($exception->getMessage()),
            default => new InternalServerErrorHttpException('Internal server error')
        };
    }
}
```

### 9. DI設定更新

#### 9.1 DomainServiceProvider更新
```php
// Children UseCase bindings
$this->app->bind(CreateChildUseCase::class);
$this->app->bind(UpdateChildUseCase::class);
$this->app->bind(DeleteChildUseCase::class);
$this->app->bind(GetChildrenQueryService::class);

// Helper bindings
$this->app->bind(TokenResolver::class);
$this->app->bind(ChildrenExceptionMapper::class);
```

## 実装ルール

### 9.1 Token処理統一
- 全てのToken→GroupId解決を統一処理
- 不正Token時の一貫したエラーレスポンス

### 9.2 ビジネスルール移行
- 名前重複チェックをDomainServiceに移行
- 削除制約チェックをDomainServiceに移行

### 9.3 レスポンス互換性
- 既存のレスポンス形式を完全維持
- フィールド名・構造の一致

## 受け入れ条件
- [ ] 4つの子ども関連Controllerが全て移行されている
- [ ] Token→GroupId解決が統一されている
- [ ] 名前重複チェックがDomainServiceで実装されている
- [ ] 削除制約チェックが実装されている
- [ ] 既存テストが全て通っている
- [ ] 新しいビジネスルールのテストが追加されている
- [ ] エラーハンドリングが統一されている
- [ ] レスポンス形式の互換性が保たれている

## 参考
- Token処理の統一でセキュリティ向上
- ビジネスルールのDomain層集約
- UseCase・QueryServiceでの責務分離