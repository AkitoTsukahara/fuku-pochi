# Task 023: DI設定・Value Objects作成

## 概要
ServiceProviderでのDIバインド設定を行い、型安全性を高めるValue Objectsを作成する。

## 目標
- interface→implementation のDIバインド設定
- 型安全なValue Objectsの作成
- 基盤的なValue Objectsの実装

## 作業内容

### 1. ServiceProvider設定

#### 1.1 DomainServiceProvider作成
- `app/Providers/DomainServiceProvider.php`
- interface → implementation のバインド設定
- Clock, UuidGenerator等の基盤クラスバインド

#### 1.2 バインド対象
```php
// 基盤サービス
ClockInterface::class => SystemClock::class
UuidGeneratorInterface::class => UuidGenerator::class

// Repository interfaces (Phase3で追加予定)
GroupRepositoryInterface::class => EloquentGroupRepository::class
ChildrenRepositoryInterface::class => EloquentChildrenRepository::class
StockRepositoryInterface::class => EloquentStockRepository::class
```

### 2. 共通Value Objects作成

#### 2.1 基盤Value Objects
- `domain/Shared/ValueObject/Uuid.php` - UUID管理
- `domain/Shared/ValueObject/Name.php` - 名前管理
- `domain/Shared/ValueObject/Count.php` - 数量管理
- `domain/Shared/ValueObject/Token.php` - トークン管理

#### 2.2 ドメイン固有Value Objects
- `domain/Group/ValueObject/GroupId.php` - グループID
- `domain/Group/ValueObject/ShareToken.php` - 共有トークン
- `domain/Children/ValueObject/ChildId.php` - 子どもID
- `domain/Stock/ValueObject/StockCount.php` - 在庫数
- `domain/ClothingCategory/ValueObject/CategoryId.php` - カテゴリID

### 3. Value Object実装ルール

#### 3.1 基本構造
```php
abstract class ValueObject
{
    protected function __construct(
        protected readonly mixed $value
    ) {
        $this->validate($value);
    }

    abstract protected function validate(mixed $value): void;
    
    public function value(): mixed
    {
        return $this->value;
    }
    
    public function equals(ValueObject $other): bool
    {
        return $this::class === $other::class 
            && $this->value === $other->value;
    }
}
```

#### 3.2 バリデーション仕様
- **Name**: 1-100文字、空白のみ禁止
- **Count**: 0以上の整数
- **Uuid**: UUID v4形式
- **Token**: 32文字の英数字
- **StockCount**: 0以上999以下の整数

### 4. 例外クラス作成

#### 4.1 Value Object例外
- `domain/Shared/Exception/InvalidValueException.php`
- 各Value Objectのバリデーション失敗時に投げる

## 受け入れ条件
- [ ] DomainServiceProviderが作成され、config/app.phpに登録されている
- [ ] 基盤サービスのDIバインドが設定されている
- [ ] 共通Value Objectsが作成されている
- [ ] ドメイン固有Value Objectsが作成されている
- [ ] 各Value Objectで適切なバリデーションが実装されている
- [ ] Value Object用の例外クラスが作成されている

## 参考
- Value Objectは不変オブジェクト（immutable）
- バリデーションはコンストラクタで実行
- Laravel依存を避け、pure PHPで実装