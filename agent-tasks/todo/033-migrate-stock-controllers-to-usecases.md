# Task 033: Stock Controller薄化・UseCase呼び出し移行・テスト修正

## 概要
Stock関連Controllerの移行とリファクタリング全体の仕上げ。複雑な在庫管理ロジックをApplication層に移行し、全テストの動作確認を行う。

## 目標
- 3つのStock Controller全ての移行
- 複雑な在庫ビジネスロジックのApplication層移行
- 全テストスイートの動作確認・修正

## 作業内容

### 1. IncrementStockController移行

#### 1.1 既存実装分析
- 現在の`IncrementStockController`の複雑なロジック確認
- firstOrCreate、在庫増加、関連データ取得の分析

#### 1.2 Controller薄化実装
```php
class IncrementStockController extends Controller
{
    public function __construct(
        private readonly IncrementStockUseCase $incrementStockUseCase,
        private readonly GetChildQueryService $getChildQueryService,
        private readonly StockExceptionMapper $exceptionMapper
    ) {}

    public function __invoke(IncrementStockRequest $request, int $id): JsonResponse
    {
        try {
            $command = IncrementStockCommand::fromRequest((string)$id, $request->validated());
            $response = $this->incrementStockUseCase->execute($command);
            
            // 子ども情報とカテゴリ情報を含む拡張レスポンス構築
            $childQuery = GetChildQuery::fromId((string)$id);
            $childResponse = $this->getChildQueryService->getById($childQuery);
            
            return $this->successResponse(
                'ストック数を増加しました',
                $this->buildStockResponse($response, $childResponse)
            );
            
        } catch (DomainException $e) {
            return $this->handleDomainException($e, $this->exceptionMapper);
        }
    }

    private function buildStockResponse(
        IncrementStockResponse $stockResponse,
        GetChildResponse $childResponse
    ): array {
        return [
            'child_id' => $stockResponse->childId,
            'child_name' => $childResponse->name,
            'stock_item' => [
                'clothing_category_id' => $stockResponse->categoryId,
                'current_count' => $stockResponse->currentCount,
                // カテゴリ詳細情報をQueryServiceから取得
            ]
        ];
    }
}
```

### 2. DecrementStockController移行

#### 2.1 在庫不足チェック強化
- UseCase内での在庫不足エラーハンドリング
- 適切なエラーメッセージ・ステータスコード設定

#### 2.2 Controller薄化実装
```php
class DecrementStockController extends Controller
{
    public function __construct(
        private readonly DecrementStockUseCase $decrementStockUseCase,
        private readonly GetChildQueryService $getChildQueryService,
        private readonly StockExceptionMapper $exceptionMapper
    ) {}

    public function __invoke(DecrementStockRequest $request, int $id): JsonResponse
    {
        try {
            $command = DecrementStockCommand::fromRequest((string)$id, $request->validated());
            $response = $this->decrementStockUseCase->execute($command);
            
            $childQuery = GetChildQuery::fromId((string)$id);
            $childResponse = $this->getChildQueryService->getById($childQuery);
            
            return $this->successResponse(
                'ストック数を減少しました',
                $this->buildStockResponse($response, $childResponse)
            );
            
        } catch (DomainException $e) {
            return $this->handleDomainException($e, $this->exceptionMapper);
        }
    }
}
```

### 3. GetStockController移行

#### 3.1 既存実装分析
- 子ども別の在庫一覧取得ロジック
- カテゴリマスタとの結合処理の確認

#### 3.2 Controller薄化実装
```php
class GetStockController extends Controller
{
    public function __construct(
        private readonly GetStockQueryService $getStockQueryService,
        private readonly StockExceptionMapper $exceptionMapper
    ) {}

    public function __invoke(int $id): JsonResponse
    {
        try {
            $query = GetStockByChildIdQuery::fromRequest((string)$id);
            $response = $this->getStockQueryService->getByChildId($query);
            
            return $this->successResponse(
                '在庫情報を取得しました',
                $response
            );
            
        } catch (DomainException $e) {
            return $this->handleDomainException($e, $this->exceptionMapper);
        }
    }
}
```

### 4. 補助QueryService追加

#### 4.1 GetChildQueryService
```php
class GetChildQueryService
{
    public function __construct(
        private readonly ChildrenRepositoryInterface $childrenRepository
    ) {}

    public function getById(GetChildQuery $query): GetChildResponse
    {
        $child = $this->childrenRepository->findById($query->childId);
        
        if (!$child) {
            throw new ChildNotFoundException();
        }

        return new GetChildResponse(
            childId: $child->getId()->value(),
            name: $child->getName()->value(),
            groupId: $child->getGroupId()->value()
        );
    }
}
```

### 5. レスポンス形式統一

#### 5.1 StockResponseBuilder
```php
class StockResponseBuilder
{
    public function __construct(
        private readonly ClothingCategoryRepositoryInterface $categoryRepository
    ) {}

    public function buildIncrementResponse(
        IncrementStockResponse $stockResponse,
        GetChildResponse $childResponse
    ): array {
        $category = $this->categoryRepository->findById(new CategoryId($stockResponse->categoryId));
        
        return [
            'child_id' => $stockResponse->childId,
            'child_name' => $childResponse->name,
            'stock_item' => [
                'id' => null, // 互換性のため
                'clothing_category_id' => $stockResponse->categoryId,
                'clothing_category' => [
                    'id' => $category->getId()->value(),
                    'name' => $category->getName()->value(),
                    'icon_path' => $category->getIconPath(),
                    'sort_order' => $category->getSortOrder(),
                ],
                'current_count' => $stockResponse->currentCount,
            ]
        ];
    }
}
```

### 6. テスト移行・修正

#### 6.1 IncrementStockControllerTest修正
```php
class IncrementStockControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_increment_stock_success()
    {
        $child = Children::factory()->create();
        $category = ClothingCategory::factory()->create();

        $response = $this->putJson("/api/children/{$child->id}/stock/increment", [
            'clothing_category_id' => $category->id,
            'increment' => 2
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'ストック数を増加しました',
                'data' => [
                    'child_id' => $child->id,
                    'child_name' => $child->name,
                    'stock_item' => [
                        'clothing_category_id' => $category->id,
                        'current_count' => 2,
                    ]
                ]
            ]);
    }

    public function test_increment_stock_child_not_found()
    {
        $category = ClothingCategory::factory()->create();

        $response = $this->putJson('/api/children/999/stock/increment', [
            'clothing_category_id' => $category->id,
            'increment' => 1
        ]);

        $response->assertStatus(404);
    }
}
```

#### 6.2 DecrementStockControllerTest修正
```php
class DecrementStockControllerTest extends TestCase
{
    public function test_decrement_stock_insufficient()
    {
        $child = Children::factory()->create();
        $category = ClothingCategory::factory()->create();
        
        // 在庫0の状態で減らそうとする
        $response = $this->putJson("/api/children/{$child->id}/stock/decrement", [
            'clothing_category_id' => $category->id,
            'decrement' => 1
        ]);

        $response->assertStatus(409) // Conflict
            ->assertJson([
                'success' => false,
                'message' => '在庫が不足しています'
            ]);
    }
}
```

### 7. 全テストスイート実行・修正

#### 7.1 テスト実行環境確認
```bash
# Laravel用テスト環境確認
php artisan test --env=testing

# 特定テストクラス実行
php artisan test tests/Feature/Stock/
php artisan test tests/Feature/Children/
php artisan test tests/Feature/Groups/
```

#### 7.2 失敗テストの修正
- DI設定不備の修正
- モッククラスの調整
- データベース状態のリセット確認

### 8. パフォーマンステスト

#### 8.1 レスポンス時間測定
```php
class PerformanceTest extends TestCase
{
    public function test_stock_increment_performance()
    {
        $startTime = microtime(true);
        
        // テスト実行
        $response = $this->putJson("/api/children/{$child->id}/stock/increment", [
            'clothing_category_id' => $category->id,
            'increment' => 1
        ]);
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        $this->assertLessThan(0.5, $executionTime, 'レスポンス時間が500ms以下であること');
    }
}
```

### 9. DI設定最終調整

#### 9.1 DomainServiceProvider完成版
```php
class DomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // 基盤サービス
        $this->app->bind(ClockInterface::class, SystemClock::class);
        $this->app->bind(UuidGeneratorInterface::class, UuidGenerator::class);
        $this->app->bind(TransactionManagerInterface::class, EloquentTransactionManager::class);

        // Repository実装
        $this->app->bind(GroupRepositoryInterface::class, EloquentGroupRepository::class);
        $this->app->bind(ChildrenRepositoryInterface::class, EloquentChildrenRepository::class);
        $this->app->bind(StockRepositoryInterface::class, EloquentStockRepository::class);
        $this->app->bind(ClothingCategoryRepositoryInterface::class, EloquentClothingCategoryRepository::class);

        // モデルアダプター
        $this->app->bind(UserGroupModelInterface::class, UserGroupModelAdapter::class);
        $this->app->bind(ChildrenModelInterface::class, ChildrenModelAdapter::class);
        $this->app->bind(StockItemModelInterface::class, StockItemModelAdapter::class);
        $this->app->bind(ClothingCategoryModelInterface::class, ClothingCategoryModelAdapter::class);

        // UseCase・QueryService・DomainService
        $this->registerUseCases();
        $this->registerQueryServices();
        $this->registerDomainServices();
        $this->registerExceptionMappers();
    }
}
```

## 実装ルール

### 9.1 レスポンス互換性維持
- 既存のレスポンス構造を完全維持
- フィールド名・ネスト構造の一致
- 関連データ取得の継続

### 9.2 エラーハンドリング強化
- ビジネスルール違反の適切な表現
- HTTP ステータスコードの統一
- ユーザーフレンドリーなエラーメッセージ

### 9.3 パフォーマンス維持
- N+1問題の回避
- 不要なクエリの削除
- レスポンス時間の監視

## 受け入れ条件
- [ ] 3つのStock ControllerがUseCase呼び出しに移行されている
- [ ] 複雑な在庫ロジックがApplication層に移行されている
- [ ] 全既存テストが通っている
- [ ] 新しいビジネスルールのテストが追加されている
- [ ] パフォーマンステストが通っている
- [ ] レスポンス形式の完全互換性が保たれている
- [ ] DI設定が完成している
- [ ] エラーハンドリングが統一されている

## 参考
- 最も複雑なStock管理ロジックの適切な分離
- 全テストスイートでのリグレッション防止
- パフォーマンス維持しながらのアーキテクチャ改善