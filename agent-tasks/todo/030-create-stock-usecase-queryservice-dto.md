# Task 030: Stock UseCase・QueryService・DTO作成

## 概要
StockコンテキストのApplication層を構築し、UseCase（Command）・QueryService・DTOを実装して在庫管理の複雑なビジネスロジックを適切に処理する。

## 目標
- 在庫増減の複雑なビジネスルール実装
- 子ども・カテゴリとの関連性を考慮した処理
- パフォーマンスを意識したデータ取得

## 作業内容

### 1. Stock UseCase（Command）作成

#### 1.1 IncrementStockUseCase
- `UseCase/Stock/Command/IncrementStockUseCase.php`
```php
class IncrementStockUseCase
{
    public function __construct(
        private readonly StockDomainService $stockDomainService,
        private readonly ValidationDomainService $validationDomainService,
        private readonly TransactionManagerInterface $transactionManager
    ) {}

    public function execute(IncrementStockCommand $command): IncrementStockResponse
    {
        return $this->transactionManager->transaction(function () use ($command) {
            // エンティティ存在チェック
            $this->validationDomainService->ensureChildExists($command->childId);
            $this->validationDomainService->ensureCategoryExists($command->categoryId);
            
            // 在庫増加処理
            $stockItem = $this->stockDomainService->incrementStock(
                $command->childId,
                $command->categoryId,
                $command->increment
            );
            
            return new IncrementStockResponse(
                childId: $stockItem->getChildId()->value(),
                categoryId: $stockItem->getCategoryId()->value(),
                currentCount: $stockItem->getCurrentCount()->value(),
                previousCount: $stockItem->getCurrentCount()->value() - $command->increment->value()
            );
        });
    }
}
```

#### 1.2 DecrementStockUseCase
- `UseCase/Stock/Command/DecrementStockUseCase.php`
```php
class DecrementStockUseCase
{
    public function execute(DecrementStockCommand $command): DecrementStockResponse
    {
        return $this->transactionManager->transaction(function () use ($command) {
            // エンティティ存在チェック
            $this->validationDomainService->ensureChildExists($command->childId);
            $this->validationDomainService->ensureCategoryExists($command->categoryId);
            
            // 在庫減少処理（在庫不足チェック含む）
            $stockItem = $this->stockDomainService->decrementStock(
                $command->childId,
                $command->categoryId,
                $command->decrement
            );
            
            return new DecrementStockResponse(
                childId: $stockItem->getChildId()->value(),
                categoryId: $stockItem->getCategoryId()->value(),
                currentCount: $stockItem->getCurrentCount()->value(),
                previousCount: $stockItem->getCurrentCount()->value() + $command->decrement->value()
            );
        });
    }
}
```

### 2. Stock QueryService作成

#### 2.1 GetStockQueryService
- `UseCase/Stock/Query/GetStockQueryService.php`
```php
class GetStockQueryService
{
    public function __construct(
        private readonly StockRepositoryInterface $stockRepository,
        private readonly ChildrenRepositoryInterface $childrenRepository,
        private readonly ClothingCategoryRepositoryInterface $categoryRepository
    ) {}

    public function getByChildId(GetStockByChildIdQuery $query): GetStockResponse
    {
        $child = $this->childrenRepository->findById($query->childId);
        
        if (!$child) {
            throw new ChildNotFoundException();
        }

        $stockItems = $this->stockRepository->findByChildId($query->childId);
        $allCategories = $this->categoryRepository->findAll();

        // 全カテゴリーの在庫情報を構築（存在しないものは0で表示）
        $stockData = [];
        foreach ($allCategories as $category) {
            $stockItem = $this->findStockItemByCategory($stockItems, $category->getId());
            
            $stockData[] = new StockItemDetailDTO(
                categoryId: $category->getId()->value(),
                categoryName: $category->getName()->value(),
                iconPath: $category->getIconPath(),
                sortOrder: $category->getSortOrder(),
                currentCount: $stockItem ? $stockItem->getCurrentCount()->value() : 0
            );
        }

        // ソート順で並び替え
        usort($stockData, fn($a, $b) => $a->sortOrder <=> $b->sortOrder);

        return new GetStockResponse(
            childId: $child->getId()->value(),
            childName: $child->getName()->value(),
            stockItems: $stockData
        );
    }

    private function findStockItemByCategory(array $stockItems, CategoryId $categoryId): ?StockItem
    {
        foreach ($stockItems as $stockItem) {
            if ($stockItem->getCategoryId()->equals($categoryId)) {
                return $stockItem;
            }
        }
        return null;
    }
}
```

### 3. Command DTO作成

#### 3.1 Increment Stock Command
- `UseCase/Stock/Command/DTO/IncrementStockCommand.php`
```php
readonly class IncrementStockCommand
{
    public function __construct(
        public ChildId $childId,
        public CategoryId $categoryId,
        public Count $increment
    ) {}

    public static function fromRequest(string $childId, array $data): self
    {
        return new self(
            childId: new ChildId($childId),
            categoryId: new CategoryId($data['clothing_category_id']),
            increment: new Count($data['increment'])
        );
    }
}
```

#### 3.2 Decrement Stock Command
- `UseCase/Stock/Command/DTO/DecrementStockCommand.php`
```php
readonly class DecrementStockCommand
{
    public function __construct(
        public ChildId $childId,
        public CategoryId $categoryId,
        public Count $decrement
    ) {}

    public static function fromRequest(string $childId, array $data): self
    {
        return new self(
            childId: new ChildId($childId),
            categoryId: new CategoryId($data['clothing_category_id']),
            decrement: new Count($data['decrement'])
        );
    }
}
```

### 4. Query DTO作成

#### 4.1 Get Stock Query
- `UseCase/Stock/Query/DTO/GetStockByChildIdQuery.php`
```php
readonly class GetStockByChildIdQuery
{
    public function __construct(
        public ChildId $childId
    ) {}

    public static function fromRequest(string $childId): self
    {
        return new self(
            childId: new ChildId($childId)
        );
    }
}
```

### 5. Response DTO作成

#### 5.1 Increment Stock Response
- `UseCase/Stock/Command/DTO/IncrementStockResponse.php`
```php
readonly class IncrementStockResponse extends ResponseDTO
{
    public function __construct(
        public string $childId,
        public string $categoryId,
        public int $currentCount,
        public int $previousCount
    ) {}

    public function toArray(): array
    {
        return [
            'child_id' => $this->childId,
            'clothing_category_id' => $this->categoryId,
            'current_count' => $this->currentCount,
            'previous_count' => $this->previousCount,
            'increment' => $this->currentCount - $this->previousCount,
        ];
    }
}
```

#### 5.2 Get Stock Response
- `UseCase/Stock/Query/DTO/GetStockResponse.php`
```php
readonly class GetStockResponse extends ResponseDTO
{
    public function __construct(
        public string $childId,
        public string $childName,
        public array $stockItems
    ) {}

    public function toArray(): array
    {
        return [
            'child_id' => $this->childId,
            'child_name' => $this->childName,
            'stock_items' => array_map(fn($item) => $item->toArray(), $this->stockItems),
        ];
    }
}
```

### 6. 詳細DTO作成

#### 6.1 Stock Item Detail DTO
- `UseCase/Stock/Query/DTO/StockItemDetailDTO.php`
```php
readonly class StockItemDetailDTO
{
    public function __construct(
        public string $categoryId,
        public string $categoryName,
        public string $iconPath,
        public int $sortOrder,
        public int $currentCount
    ) {}

    public function toArray(): array
    {
        return [
            'clothing_category_id' => $this->categoryId,
            'clothing_category' => [
                'id' => $this->categoryId,
                'name' => $this->categoryName,
                'icon_path' => $this->iconPath,
                'sort_order' => $this->sortOrder,
            ],
            'current_count' => $this->currentCount,
        ];
    }
}
```

### 7. 複合QueryService作成

#### 7.1 GetChildStockSummaryQueryService
- `UseCase/Stock/Query/GetChildStockSummaryQueryService.php`
```php
class GetChildStockSummaryQueryService
{
    public function execute(GetChildStockSummaryQuery $query): GetChildStockSummaryResponse
    {
        $children = $this->childrenRepository->findByGroupId($query->groupId);
        
        $childrenWithStock = [];
        foreach ($children as $child) {
            $stockItems = $this->stockRepository->findByChildId($child->getId());
            $totalStockCount = array_sum(array_map(
                fn($item) => $item->getCurrentCount()->value(),
                $stockItems
            ));

            $childrenWithStock[] = new ChildStockSummaryDTO(
                childId: $child->getId()->value(),
                childName: $child->getName()->value(),
                totalStockCount: $totalStockCount,
                stockItemCount: count($stockItems)
            );
        }

        return new GetChildStockSummaryResponse(
            groupId: $query->groupId->value(),
            children: $childrenWithStock
        );
    }
}
```

### 8. Exception Mapping作成

#### 8.1 StockExceptionMapper
- `UseCase/Stock/Exception/StockExceptionMapper.php`
```php
class StockExceptionMapper
{
    public function mapToHttpException(DomainException $exception): HttpException
    {
        return match ($exception::class) {
            ChildNotFoundException::class => new NotFoundHttpException('指定された子どもが見つかりません'),
            ClothingCategoryNotFoundException::class => new NotFoundHttpException('指定されたカテゴリが見つかりません'),
            InsufficientStockException::class => new ConflictHttpException($exception->getMessage()),
            StockLimitExceededException::class => new UnprocessableEntityHttpException($exception->getMessage()),
            InvalidValueException::class => new UnprocessableEntityHttpException($exception->getMessage()),
            default => new InternalServerErrorHttpException('Internal server error')
        };
    }
}
```

## 実装ルール

### 8.1 在庫管理ルール
- 在庫アイテムが存在しない場合は自動生成（count=0）
- 全カテゴリーの在庫情報を表示（0も含む）
- 在庫上限・下限のビジネスルールチェック

### 8.2 パフォーマンス考慮
- 1回のクエリで必要データを取得
- N+1問題の回避
- カテゴリーマスタのキャッシュ利用

### 8.3 データ整合性
- トランザクション内での在庫更新
- 同時更新制御の考慮

## 受け入れ条件
- [ ] Stock関連のUseCaseが作成されている
- [ ] Stock関連のQueryServiceが作成されている
- [ ] Command・Query・Response DTOが作成されている
- [ ] 在庫増減のビジネスルールが実装されている
- [ ] 全カテゴリー表示機能が実装されている
- [ ] パフォーマンスを考慮したクエリが実装されている
- [ ] 例外マッピング機能が実装されている
- [ ] データ整合性が保証されている

## 参考
- 在庫管理の複雑なビジネスルールをDomainServiceで処理
- QueryServiceで効率的なデータ取得を実現
- 全カテゴリー表示でUXを向上