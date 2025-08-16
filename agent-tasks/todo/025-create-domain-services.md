# Task 025: DomainService作成

## 概要
ビジネスロジック・分岐を集約するDomainServiceを作成し、複数エンティティにまたがるドメインロジックを実装する。

## 目標
- 業務ロジック・分岐をDomainServiceに集約
- エンティティ間の複雑な操作をサービスとして実装
- ドメイン固有の不変条件・ビジネスルールの検証

## 作業内容

### 1. Group DomainService作成

#### 1.1 GroupDomainService
- `domain/Group/Service/GroupDomainService.php`
```php
class GroupDomainService
{
    public function __construct(
        private readonly GroupRepositoryInterface $groupRepository,
        private readonly UuidGeneratorInterface $uuidGenerator
    ) {}

    // グループ作成時のビジネスルール
    public function createGroup(Name $name): Group;
    
    // 共有トークンの重複チェック・再生成
    public function regenerateShareToken(Group $group): void;
    
    // グループ削除時の整合性チェック
    public function canDeleteGroup(GroupId $groupId): bool;
}
```

### 2. Children DomainService作成

#### 2.1 ChildrenDomainService
- `domain/Children/Service/ChildrenDomainService.php`
```php
class ChildrenDomainService
{
    public function __construct(
        private readonly ChildrenRepositoryInterface $childrenRepository,
        private readonly GroupRepositoryInterface $groupRepository,
        private readonly UuidGeneratorInterface $uuidGenerator
    ) {}

    // 子ども作成時のビジネスルール
    public function createChild(GroupId $groupId, Name $name): Child;
    
    // 同一グループ内での名前重複チェック
    public function isDuplicateName(GroupId $groupId, Name $name, ?ChildId $excludeChildId = null): bool;
    
    // 子ども削除時の整合性チェック（在庫データの扱い）
    public function canDeleteChild(ChildId $childId): bool;
}
```

### 3. Stock DomainService作成

#### 3.1 StockDomainService
- `domain/Stock/Service/StockDomainService.php`
```php
class StockDomainService
{
    public function __construct(
        private readonly StockRepositoryInterface $stockRepository,
        private readonly ChildrenRepositoryInterface $childrenRepository,
        private readonly ClothingCategoryRepositoryInterface $categoryRepository
    ) {}

    // 在庫増加時のビジネスルール
    public function incrementStock(ChildId $childId, CategoryId $categoryId, Count $increment): StockItem;
    
    // 在庫減少時のビジネスルール  
    public function decrementStock(ChildId $childId, CategoryId $categoryId, Count $decrement): StockItem;
    
    // 在庫アイテム初期化
    public function initializeStockItem(ChildId $childId, CategoryId $categoryId): StockItem;
    
    // 在庫数の上限チェック
    public function validateStockLimit(StockCount $currentCount, Count $increment): bool;
}
```

### 4. 共通DomainService

#### 4.1 ValidationDomainService
- `domain/Shared/Service/ValidationDomainService.php`
```php
class ValidationDomainService
{
    // エンティティ存在チェック
    public function ensureGroupExists(GroupId $groupId): void;
    public function ensureChildExists(ChildId $childId): void;
    public function ensureCategoryExists(CategoryId $categoryId): void;
    
    // ビジネスルール違反例外
    private function throwBusinessRuleViolation(string $message): never;
}
```

### 5. ドメインイベント定義

#### 5.1 Group関連イベント
- `domain/Group/Event/GroupCreated.php`
- `domain/Group/Event/ShareTokenRegenerated.php`

#### 5.2 Children関連イベント  
- `domain/Children/Event/ChildCreated.php`
- `domain/Children/Event/ChildDeleted.php`

#### 5.3 Stock関連イベント
- `domain/Stock/Event/StockIncremented.php`
- `domain/Stock/Event/StockDecremented.php`

### 6. ドメイン例外定義

#### 6.1 ビジネスルール違反例外
- `domain/Shared/Exception/BusinessRuleViolationException.php`
- `domain/Group/Exception/GroupNotFoundException.php`
- `domain/Children/Exception/ChildNotFoundException.php`
- `domain/Children/Exception/DuplicateChildNameException.php`
- `domain/Stock/Exception/InsufficientStockException.php`
- `domain/Stock/Exception/StockLimitExceededException.php`

## 実装ルール

### 6.1 DomainService責務
- 複数エンティティにまたがるビジネスロジック
- ドメイン不変条件の検証
- エンティティの生成・状態変更の調整

### 6.2 例外処理方針
- ビジネスルール違反はDomainExceptionで表現
- 適切なメッセージとエラーコードを設定
- 例外はApp層でHTTPレスポンスにマッピング

### 6.3 イベント発火タイミング
- 重要な状態遷移時にドメインイベント発火
- 副作用処理（メール、通知）はInfraのリスナーで実装

## 受け入れ条件
- [ ] 各ドメインのDomainServiceが作成されている
- [ ] ビジネスロジック・分岐がサービスに集約されている
- [ ] ドメインイベントが適切に定義されている
- [ ] ドメイン例外が定義されている
- [ ] 不変条件の検証が実装されている
- [ ] Laravel依存がない（Repository経由でのみデータアクセス）
- [ ] エンティティ間の複雑な操作がサービスとして実装されている

## 参考
- DomainServiceは複数エンティティにまたがるロジックを扱う
- 単一エンティティ内で完結するロジックはエンティティメソッドで実装
- ドメインイベントで疎結合な副作用処理を実現