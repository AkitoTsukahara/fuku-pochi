# バックエンドクリーンアーキテクチャリファクタリング戦略

## 📋 概要

2025年のリファクタリングにより、既存のLaravelアプリケーションをクリーンアーキテクチャに移行。  
**Stranglerパターン**で段階的に移行し、既存機能を壊すことなくアーキテクチャを改善。

## 🎯 目標

- **責務分離**: Controller層からビジネスロジックを分離
- **保守性向上**: Domain層のLaravel非依存化
- **テスト容易性**: 層別テスト戦略の確立
- **運用性向上**: 統一的なログ・監視体制の構築

## 🏗️ アーキテクチャ設計

### 依存方向
```
App → Domain ← Infra
```

### 層構成

| 層 | 責務 | 実装方針 |
|---|-----|---------|
| **Presentation** | HTTP処理・認証 | Controller薄化・UseCase呼び出し |
| **Application** | ユースケース実行 | CQRS・トランザクション管理 |
| **Domain** | ビジネスロジック | Laravel非依存・純粋PHP |
| **Infrastructure** | データ永続化 | Repository実装・Eloquentアダプター |

## 📂 ディレクトリ構造

```
backend/app/
├── Http/Controllers/Api/        # 薄化されたController
├── UseCase/                     # Application層
│   ├── Group/Command/           # 更新系UseCase
│   ├── Group/Query/             # 参照系QueryService
│   └── Group/DTO/               # データ転送オブジェクト
├── domain/                      # Domain層（Laravel非依存）
│   ├── Group/Entity/            # ドメインエンティティ
│   ├── Group/ValueObject/       # 値オブジェクト
│   ├── Group/Repository/        # Repository interface
│   └── Group/Service/           # ドメインサービス
└── infra/                       # Infrastructure層
    ├── Group/Persistence/       # Repository実装
    └── Group/Adapter/           # Eloquentアダプター
```

## 🚀 段階的移行戦略

### Phase 1: 基盤構築 (タスク022-023)
1. ディレクトリ構造・基盤クラス作成
2. DI設定・Value Objects作成

### Phase 2: Domain層構築 (タスク024-025)
1. Entity・Repository interfaces作成
2. DomainService作成

### Phase 3: Infrastructure層構築 (タスク026-027)
1. Repository実装作成
2. Eloquentアダプター作成

### Phase 4: Application層構築 (タスク028-030)
1. Group UseCase・QueryService・DTO作成
2. Children UseCase・QueryService・DTO作成
3. Stock UseCase・QueryService・DTO作成

### Phase 5: 段階的移行 (タスク031-033)
1. Group Controller薄化・UseCase呼び出し移行
2. Children Controller薄化・UseCase呼び出し移行
3. Stock Controller薄化・UseCase呼び出し移行・テスト修正

### Phase 6: 最終調整 (タスク034-035)
1. エラーハンドリング統一・例外マッピング
2. ログ監視・契約テスト追加

## 🎨 実装パターン

### Controller薄化パターン
```php
class CreateGroupController extends Controller
{
    public function __invoke(CreateGroupRequest $request): JsonResponse
    {
        try {
            $command = CreateGroupCommand::fromRequest($request->validated());
            $response = $this->createGroupUseCase->execute($command);
            
            return $this->successResponse('グループが正常に作成されました', $response);
        } catch (DomainException $e) {
            return $this->handleDomainException($e, $this->exceptionMapper);
        }
    }
}
```

### UseCase実装パターン
```php
class CreateGroupUseCase
{
    public function execute(CreateGroupCommand $command): CreateGroupResponse
    {
        return $this->transactionManager->transaction(function () use ($command) {
            $group = $this->groupDomainService->createGroup($command->name);
            
            return new CreateGroupResponse(
                groupId: $group->getId()->value(),
                name: $group->getName()->value(),
                shareToken: $group->getShareToken()->value()
            );
        });
    }
}
```

### Value Object実装パターン
```php
class Name extends ValueObject
{
    public function __construct(protected readonly string $value)
    {
        $this->validate($value);
    }

    protected function validate(string $value): void
    {
        if (empty(trim($value))) {
            throw new InvalidValueException('名前は必須です');
        }
    }
}
```

## 🔒 実装ルール

### Domain層禁止事項
- Laravel/Eloquent/Request/Carbon/DB の直接使用
- フレームワーク固有クラスの import
- 副作用のある処理（ログ出力、外部API呼び出し）

### CQRS原則
- **UseCase**: 更新系処理・トランザクション管理
- **QueryService**: 参照系処理・副作用なし

### エラーハンドリング統一
```php
// Domain例外 → HTTP例外マッピング
BusinessRuleViolationException → 409 Conflict
GroupNotFoundException → 404 Not Found
InvalidValueException → 422 Unprocessable Entity
```

## 🧪 テスト戦略

### テスト階層
```
Unit Tests (Domain層)
    ↓
Integration Tests (Application層)
    ↓
Infrastructure Tests (Repository実装)
    ↓
Contract Tests (Repository互換性)
    ↓
Feature Tests (APIエンドポイント)
    ↓
Performance Tests (性能監視)
```

### テスト目標
- **Domain層**: 95%以上カバレッジ
- **API レスポンス時間**: 500ms以下
- **N+1クエリ**: 回避必須

## 📊 運用・監視

### 構造化ログ
```php
Log::channel('business_events')->info('Group created', [
    'event_type' => 'group_created',
    'group_id' => $groupId,
    'timestamp' => now()->toISOString(),
]);
```

### エラー追跡
```json
{
  "success": false,
  "message": "ユーザーフレンドリーなメッセージ",
  "error_code": "BUSINESS_RULE_VIOLATION",
  "trace_id": "trace_674a1b2c3d4e5f",
  "data": null
}
```

### ヘルスチェック
- `/api/health` エンドポイント
- データベース接続・Repository動作確認
- パフォーマンス監視・アラート

## 📈 期待効果

### アーキテクチャ面
- ✅ Controller層の薄化とビジネスロジック分離
- ✅ Domain層のLaravel非依存化
- ✅ CQRS実装による読み書き分離
- ✅ Repository パターンによるデータアクセス抽象化

### 保守性面
- ✅ UseCase・DomainServiceによる責務明確化
- ✅ Value Objectによる型安全性向上
- ✅ 統一的なエラーハンドリング
- ✅ 構造化ログによる追跡可能性

### テスト面
- ✅ 層別テスト・契約テスト・パフォーマンステスト
- ✅ インメモリ実装によるテスト高速化
- ✅ Repository実装差し替え可能性の保証

### 運用面
- ✅ ヘルスチェック・メトリクス・アラート
- ✅ 監視体制による問題の早期発見
- ✅ ログ分析による運用改善

## 🚨 注意事項

### 移行時の注意点
1. **レスポンス形式の完全互換性維持**
2. **既存テストの全パス確認**
3. **段階的移行による安全性確保**
4. **パフォーマンス劣化の監視**

### レビューポイント
- [ ] Domain層にLaravel依存がないか
- [ ] UseCase/QueryServiceで適切にCQRSが実装されているか
- [ ] Value Objectが適切に使用されているか
- [ ] エラーハンドリングが統一されているか
- [ ] テストが適切なレベルで書かれているか

## 📚 参考ドキュメント

- [clean-architecture-guidelines.md](./clean-architecture-guidelines.md) - 詳細実装ガイドライン
- [controller-guidelines.md](./controller-guidelines.md) - Controller実装方針
- [testing-guidelines.md](./testing-guidelines.md) - テスト戦略
- [architecture.md](./architecture.md) - 全体アーキテクチャ

## 🏁 完了基準

**12タスク全完了時に以下が達成される:**
- 既存機能を壊すことなくクリーンアーキテクチャへの完全移行
- 保守性・テスト容易性・運用性の大幅向上
- 長期的な技術的負債の削減と開発効率の改善

---

*この戦略文書は開発チーム全体で共有し、リファクタリング作業の指針として活用してください。*