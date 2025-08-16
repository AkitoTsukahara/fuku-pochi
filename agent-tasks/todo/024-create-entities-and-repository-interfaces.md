# Task 024: Entity・Repository interfaces作成

## 概要
ドメインエンティティとRepositoryインターフェースを作成し、ドメインの中核となるビジネスオブジェクトを定義する。

## 目標
- ドメインエンティティの作成（Laravel非依存）
- Repositoryインターフェースの定義
- 検索条件・ページング仕様の統一

## 作業内容

### 1. ドメインエンティティ作成

#### 1.1 Group関連エンティティ
- `domain/Group/Entity/Group.php`
```php
// フィールド: GroupId, Name, ShareToken, 作成日時
// メソッド: 子ども追加可能チェック、トークン再生成など
```

#### 1.2 Children関連エンティティ
- `domain/Children/Entity/Child.php`
```php
// フィールド: ChildId, GroupId, Name, 作成日時
// メソッド: 所属グループ変更、名前変更など
```

#### 1.3 Stock関連エンティティ
- `domain/Stock/Entity/StockItem.php`
```php
// フィールド: ChildId, CategoryId, StockCount
// メソッド: 増加、減少、数量チェックなど
```

#### 1.4 ClothingCategory関連エンティティ
- `domain/ClothingCategory/Entity/ClothingCategory.php`
```php
// フィールド: CategoryId, Name, IconPath, SortOrder
// 読み取り専用エンティティ
```

### 2. Repositoryインターフェース作成

#### 2.1 Group Repository
- `domain/Group/Repository/GroupRepositoryInterface.php`
```php
interface GroupRepositoryInterface
{
    public function findById(GroupId $groupId): ?Group;
    public function findByShareToken(ShareToken $token): ?Group;
    public function save(Group $group): void;
    public function delete(GroupId $groupId): void;
}
```

#### 2.2 Children Repository  
- `domain/Children/Repository/ChildrenRepositoryInterface.php`
```php
interface ChildrenRepositoryInterface
{
    public function findById(ChildId $childId): ?Child;
    public function findByGroupId(GroupId $groupId): array;
    public function save(Child $child): void;
    public function delete(ChildId $childId): void;
}
```

#### 2.3 Stock Repository
- `domain/Stock/Repository/StockRepositoryInterface.php`
```php
interface StockRepositoryInterface
{
    public function findByChildAndCategory(ChildId $childId, CategoryId $categoryId): ?StockItem;
    public function findByChildId(ChildId $childId): array;
    public function save(StockItem $stockItem): void;
    public function delete(ChildId $childId, CategoryId $categoryId): void;
}
```

#### 2.4 ClothingCategory Repository
- `domain/ClothingCategory/Repository/ClothingCategoryRepositoryInterface.php`
```php
interface ClothingCategoryRepositoryInterface
{
    public function findById(CategoryId $categoryId): ?ClothingCategory;
    public function findAll(): array;
}
```

### 3. 検索条件・ページング仕様

#### 3.1 Criteria実装
- `domain/Group/Repository/Criteria/GroupCriteria.php`
- `domain/Children/Repository/Criteria/ChildrenCriteria.php`
- `domain/Stock/Repository/Criteria/StockCriteria.php`

#### 3.2 PageResult実装
- `domain/Shared/Repository/PageResult.php`
```php
class PageResult
{
    public function __construct(
        public readonly array $items,
        public readonly int $totalCount,
        public readonly int $currentPage,
        public readonly int $perPage
    ) {}
}
```

### 4. Entity実装ルール

#### 4.1 基本構造
- Entity基底クラスを継承
- Value Objectを利用した型安全な実装
- ビジネスロジックをメソッドとして実装

#### 4.2 不変条件の実装
- コンストラクタでの必須項目チェック
- メソッド実行時のビジネスルール検証
- 状態変更時の整合性チェック

## 受け入れ条件
- [ ] 各ドメインエンティティが作成されている
- [ ] Value Objectを使用した型安全な実装になっている
- [ ] Repositoryインターフェースが適切に定義されている
- [ ] 検索条件（Criteria）クラスが作成されている
- [ ] PageResultクラスが実装されている
- [ ] Laravel依存がない（pure PHPで実装）
- [ ] ビジネスロジックがエンティティのメソッドとして実装されている

## 参考
- エンティティは一意性を持つドメインオブジェクト
- Repository パターンでデータ永続化を抽象化
- Criteriaパターンで複雑な検索条件を表現