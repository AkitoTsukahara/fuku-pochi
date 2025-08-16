# Task 022: ディレクトリ構造・基盤クラス作成

## 概要
クリーンアーキテクチャの基盤となるディレクトリ構造を作成し、各層の基本クラス・インターフェースを定義する。

## 目標
- クリーンアーキテクチャの3層構造を構築
- 各層の基盤クラス・インターフェースを定義
- 依存方向（App → Domain ← Infra）を確立

## 作業内容

### 1. ディレクトリ構造作成
```
backend/app/
├── UseCase/                    # Application Layer
│   ├── Group/
│   ├── Children/
│   └── Stock/
├── domain/                     # Domain Layer
│   ├── Group/
│   ├── Children/
│   ├── Stock/
│   ├── Shared/
│   └── ClothingCategory/
└── infra/                      # Infrastructure Layer
    ├── Group/
    ├── Children/
    ├── Stock/
    └── ClothingCategory/
```

### 2. Domain基盤クラス作成

#### 2.1 基盤抽象クラス
- `domain/Shared/Entity.php` - Entityの基底クラス
- `domain/Shared/ValueObject.php` - Value Objectの基底クラス
- `domain/Shared/DomainEvent.php` - ドメインイベントの基底クラス
- `domain/Shared/DomainException.php` - ドメイン例外の基底クラス

#### 2.2 共通インターフェース
- `domain/Shared/Repository/Criteria.php` - 検索条件インターフェース
- `domain/Shared/Repository/PageResult.php` - ページング結果クラス
- `domain/Shared/Clock/ClockInterface.php` - 時刻取得インターフェース
- `domain/Shared/Id/UuidGeneratorInterface.php` - UUID生成インターフェース

### 3. Application基盤クラス作成

#### 3.1 UseCase基盤
- `UseCase/Shared/UseCase.php` - UseCase基底クラス
- `UseCase/Shared/QueryService.php` - QueryService基底クラス
- `UseCase/Shared/DTO/ResponseDTO.php` - レスポンスDTO基底クラス

### 4. Infrastructure基盤クラス作成

#### 4.1 Repository基盤
- `infra/Shared/Repository/EloquentRepository.php` - Eloquent Repository基底クラス
- `infra/Shared/Clock/SystemClock.php` - システム時刻実装
- `infra/Shared/Id/UuidGenerator.php` - UUID生成実装

## 受け入れ条件
- [ ] 3層のディレクトリ構造が作成されている
- [ ] Domain層の基盤クラス・インターフェースが定義されている
- [ ] Application層の基盤クラスが定義されている
- [ ] Infrastructure層の基盤クラスが定義されている
- [ ] 依存方向が正しく設定されている（Laravelクラスを直接importしない）

## 参考
- 依存方向: App → Domain ← Infra
- Domain層では Laravel/Eloquent/Request/Carbon/DB を禁止
- 共通機能は抽象インターフェース経由で注入