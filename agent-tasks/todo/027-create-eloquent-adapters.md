# Task 027: Eloquentアダプター作成

## 概要
既存のEloquentモデルをDomain層から切り離し、アダプターパターンでEloquentへの依存を局所化する。

## 目標
- 既存EloquentモデルとRepository実装の橋渡し
- Domain層のLaravel依存完全排除
- Eloquentモデルの機能をアダプター経由で利用

## 作業内容

### 1. Eloquentモデルアダプター作成

#### 1.1 UserGroupModelAdapter
- `infra/Group/Persistence/Adapter/UserGroupModelAdapter.php`
```php
class UserGroupModelAdapter implements UserGroupModelInterface
{
    public function __construct(
        private readonly UserGroup $model
    ) {}

    public function find(string $id): ?UserGroup
    {
        return $this->model->find($id);
    }

    public function findByShareToken(string $token): ?UserGroup
    {
        return $this->model->where('share_token', $token)->first();
    }

    public function create(array $attributes): UserGroup
    {
        return $this->model->create($attributes);
    }

    public function update(string $id, array $attributes): bool
    {
        return $this->model->where('id', $id)->update($attributes) > 0;
    }

    public function delete(string $id): bool
    {
        return $this->model->destroy($id) > 0;
    }
}
```

#### 1.2 ChildrenModelAdapter
- `infra/Children/Persistence/Adapter/ChildrenModelAdapter.php`
```php
class ChildrenModelAdapter implements ChildrenModelInterface
{
    public function find(string $id): ?Children;
    public function findByGroupId(string $groupId): Collection;
    public function create(array $attributes): Children;
    public function update(string $id, array $attributes): bool;
    public function delete(string $id): bool;
    public function existsByGroupIdAndName(string $groupId, string $name, ?string $excludeId = null): bool;
}
```

#### 1.3 StockItemModelAdapter
- `infra/Stock/Persistence/Adapter/StockItemModelAdapter.php`
```php
class StockItemModelAdapter implements StockItemModelInterface
{
    public function findByChildAndCategory(string $childId, string $categoryId): ?StockItem;
    public function findByChildId(string $childId): Collection;
    public function firstOrCreate(array $search, array $create): StockItem;
    public function update(string $childId, string $categoryId, array $attributes): bool;
    public function delete(string $childId, string $categoryId): bool;
}
```

#### 1.4 ClothingCategoryModelAdapter
- `infra/ClothingCategory/Persistence/Adapter/ClothingCategoryModelAdapter.php`
```php
class ClothingCategoryModelAdapter implements ClothingCategoryModelInterface
{
    public function find(string $id): ?ClothingCategory;
    public function findAll(): Collection;
    public function findAllOrdered(): Collection;
}
```

### 2. データ変換ヘルパー作成

#### 2.1 Domain→Eloquent変換
- `infra/Shared/Converter/DomainToEloquentConverter.php`
```php
class DomainToEloquentConverter
{
    public function convertGroup(Group $group): array
    {
        return [
            'id' => $group->getId()->value(),
            'name' => $group->getName()->value(),
            'share_token' => $group->getShareToken()->value(),
            // created_at, updated_atはEloquent側で自動設定
        ];
    }

    public function convertChild(Child $child): array;
    public function convertStockItem(StockItem $stockItem): array;
}
```

#### 2.2 Eloquent→Domain変換
- `infra/Shared/Converter/EloquentToDomainConverter.php`
```php
class EloquentToDomainConverter
{
    public function convertGroup(UserGroup $model): Group
    {
        return new Group(
            new GroupId($model->id),
            new Name($model->name),
            new ShareToken($model->share_token),
            $model->created_at,
            $model->updated_at
        );
    }

    public function convertChild(Children $model): Child;
    public function convertStockItem(StockItem $model): Stock\Entity\StockItem;
    public function convertClothingCategory(ClothingCategory $model): ClothingCategory\Entity\ClothingCategory;
}
```

### 3. クエリビルダーアダプター

#### 3.1 EloquentQueryBuilder
- `infra/Shared/Query/EloquentQueryBuilderAdapter.php`
```php
class EloquentQueryBuilderAdapter
{
    public function __construct(
        private readonly Builder $builder
    ) {}

    public function where(string $column, mixed $value): self;
    public function whereIn(string $column, array $values): self;
    public function orderBy(string $column, string $direction = 'asc'): self;
    public function limit(int $limit): self;
    public function offset(int $offset): self;
    public function get(): Collection;
    public function first(): ?Model;
    public function count(): int;
}
```

### 4. 既存Eloquentモデル調整

#### 4.1 UserGroupモデル調整
既存の`app/Models/UserGroup.php`に以下を追加：
- UUID主キー対応
- タイムスタンプ取得メソッド
- リレーション調整

#### 4.2 Childrenモデル調整
既存の`app/Models/Children.php`に以下を追加：
- UUID主キー対応
- 検索用スコープメソッド

#### 4.3 StockItemモデル調整
既存の`app/Models/StockItem.php`に以下を追加：
- 複合キー対応
- 検索用スコープメソッド

### 5. サービスプロバイダーバインド更新

#### 5.1 DomainServiceProvider更新
```php
// アダプターのバインド追加
$this->app->bind(UserGroupModelInterface::class, function () {
    return new UserGroupModelAdapter(new UserGroup());
});

$this->app->bind(ChildrenModelInterface::class, function () {
    return new ChildrenModelAdapter(new Children());
});

$this->app->bind(StockItemModelInterface::class, function () {
    return new StockItemModelAdapter(new StockItem());
});

$this->app->bind(ClothingCategoryModelInterface::class, function () {
    return new ClothingCategoryModelAdapter(new ClothingCategory());
});
```

## 実装ルール

### 5.1 アダプター責務
- EloquentモデルのCRUD操作を抽象化
- Laravel固有の機能（Collection等）をアダプター内に封じ込め
- Domain層からEloquent依存を完全排除

### 5.2 エラーハンドリング
- Eloquent例外をキャッチして適切なDomain例外に変換
- データベース制約違反の検出

### 5.3 パフォーマンス最適化
- Lazy Loading回避のためのEager Loading設定
- 必要最小限のクエリ実行

## 受け入れ条件
- [ ] 各Eloquentモデルのアダプターが作成されている
- [ ] データ変換ヘルパーが実装されている
- [ ] 既存Eloquentモデルが調整されている
- [ ] サービスプロバイダーのバインドが更新されている
- [ ] クエリビルダーアダプターが実装されている
- [ ] Domain層からEloquent依存が完全に排除されている
- [ ] エラーハンドリングが適切に実装されている

## 参考
- アダプターパターンで既存コードを活用
- EloquentモデルはInfra層のアダプター内に封じ込め
- Domain層は純粋なPHPクラスのみで構成