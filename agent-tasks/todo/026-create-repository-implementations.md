# Task 026: Repository実装作成

## 概要
Domain層で定義したRepositoryインターフェースのInfrastructure層での実装を作成し、EloquentORMとDomainエンティティの橋渡しを行う。

## 目標
- RepositoryインターフェースのEloquent実装
- EloquentモデルとDomainエンティティの変換ロジック
- 検索条件（Criteria）のEloquentクエリ変換

## 作業内容

### 1. Group Repository実装

#### 1.1 EloquentGroupRepository
- `infra/Group/Persistence/EloquentGroupRepository.php`
```php
class EloquentGroupRepository implements GroupRepositoryInterface
{
    public function __construct(
        private readonly UserGroupModelInterface $userGroupModel
    ) {}

    public function findById(GroupId $groupId): ?Group;
    public function findByShareToken(ShareToken $token): ?Group;
    public function save(Group $group): void;
    public function delete(GroupId $groupId): void;
    
    // EloquentモデルからDomainエンティティへの変換
    private function toDomain(UserGroup $model): Group;
    
    // DomainエンティティからEloquentモデルへの変換
    private function toEloquent(Group $group): array;
}
```

### 2. Children Repository実装

#### 2.1 EloquentChildrenRepository
- `infra/Children/Persistence/EloquentChildrenRepository.php`
```php
class EloquentChildrenRepository implements ChildrenRepositoryInterface
{
    public function __construct(
        private readonly ChildrenModelInterface $childrenModel
    ) {}

    public function findById(ChildId $childId): ?Child;
    public function findByGroupId(GroupId $groupId): array;
    public function save(Child $child): void;
    public function delete(ChildId $childId): void;
    
    private function toDomain(Children $model): Child;
    private function toEloquent(Child $child): array;
}
```

### 3. Stock Repository実装

#### 3.1 EloquentStockRepository
- `infra/Stock/Persistence/EloquentStockRepository.php`
```php
class EloquentStockRepository implements StockRepositoryInterface
{
    public function __construct(
        private readonly StockItemModelInterface $stockItemModel
    ) {}

    public function findByChildAndCategory(ChildId $childId, CategoryId $categoryId): ?StockItem;
    public function findByChildId(ChildId $childId): array;
    public function save(StockItem $stockItem): void;
    public function delete(ChildId $childId, CategoryId $categoryId): void;
    
    private function toDomain(StockItem $model): Stock\Entity\StockItem;
    private function toEloquent(Stock\Entity\StockItem $stockItem): array;
}
```

### 4. ClothingCategory Repository実装

#### 4.1 EloquentClothingCategoryRepository
- `infra/ClothingCategory/Persistence/EloquentClothingCategoryRepository.php`
```php
class EloquentClothingCategoryRepository implements ClothingCategoryRepositoryInterface
{
    public function __construct(
        private readonly ClothingCategoryModelInterface $clothingCategoryModel
    ) {}

    public function findById(CategoryId $categoryId): ?ClothingCategory;
    public function findAll(): array;
    
    private function toDomain(ClothingCategory $model): ClothingCategory\Entity\ClothingCategory;
}
```

### 5. Eloquentモデルインターフェース作成

#### 5.1 Eloquentモデル抽象化
- `infra/Shared/Model/UserGroupModelInterface.php`
- `infra/Shared/Model/ChildrenModelInterface.php`
- `infra/Shared/Model/StockItemModelInterface.php`
- `infra/Shared/Model/ClothingCategoryModelInterface.php`

```php
interface UserGroupModelInterface
{
    public function find(string $id): ?UserGroup;
    public function create(array $attributes): UserGroup;
    public function update(string $id, array $attributes): bool;
    public function delete(string $id): bool;
    // etc...
}
```

### 6. Criteria→Eloquentクエリ変換

#### 6.1 CriteriaBuilder実装
- `infra/Shared/Query/EloquentCriteriaBuilder.php`
```php
class EloquentCriteriaBuilder
{
    public function applyGroupCriteria(Builder $query, GroupCriteria $criteria): Builder;
    public function applyChildrenCriteria(Builder $query, ChildrenCriteria $criteria): Builder;
    public function applyStockCriteria(Builder $query, StockCriteria $criteria): Builder;
}
```

### 7. トランザクション管理

#### 7.1 Eloquentトランザクションマネージャー
- `infra/Shared/Transaction/EloquentTransactionManager.php`
```php
interface TransactionManagerInterface
{
    public function beginTransaction(): void;
    public function commit(): void;
    public function rollback(): void;
    public function transaction(callable $callback): mixed;
}

class EloquentTransactionManager implements TransactionManagerInterface
{
    // Laravel DBファサードを使用したトランザクション管理
}
```

## 実装ルール

### 7.1 変換ルール
- EloquentモデルのフィールドはValue Objectに変換
- null値の適切な処理
- 作成・更新日時の変換

### 7.2 エラーハンドリング
- Eloquent例外をDomain例外にマッピング
- データベース制約違反の適切な処理

### 7.3 パフォーマンス考慮
- N+1問題回避のためのEager Loading
- 必要最小限のフィールドのみ取得

## 受け入れ条件
- [ ] 各RepositoryインターフェースのEloquent実装が作成されている
- [ ] EloquentモデルとDomainエンティティの変換が実装されている
- [ ] Eloquentモデルインターフェースが定義されている
- [ ] Criteria→Eloquentクエリ変換が実装されている
- [ ] トランザクション管理機能が実装されている
- [ ] エラーハンドリングが適切に実装されている
- [ ] Domain層に依存せず、Infra層で完結している

## 参考
- Repository パターンでORMを抽象化
- EloquentモデルはInfra層に閉じ込める
- Domain層からLaravel依存を完全に排除