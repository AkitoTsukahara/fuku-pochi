# クリーンアーキテクチャ実装ガイドライン

## 概要

本プロジェクトは2025年のリファクタリングにより、クリーンアーキテクチャを採用。既存機能を壊すことなく、段階的にStranglerパターンで移行を実施。

## アーキテクチャ原則

### 依存方向

```
App → Domain ← Infra
```

- **App層**: Domain層のみに依存
- **Domain層**: 他の層に依存しない（純粋PHP）
- **Infra層**: Domain層のインターフェースに依存

### 禁止事項

**Domain層での禁止事項:**
- Laravel/Eloquent/Request/Carbon/DB の直接使用
- フレームワーク固有クラスの import
- 副作用のある処理（ログ出力、外部API呼び出し）

## 各層の実装ルール

### 1. Domain層 (`backend/app/domain/`)

#### Entity実装ルール
```php
// ✅ 良い例
class Group extends Entity
{
    public function __construct(
        private readonly GroupId $id,
        private readonly Name $name,
        private readonly ShareToken $shareToken,
        private readonly ?Carbon $createdAt = null,
        private readonly ?Carbon $updatedAt = null
    ) {}

    public function regenerateShareToken(UuidGeneratorInterface $generator): void
    {
        $this->shareToken = new ShareToken($generator->generate());
    }
}

// ❌ 悪い例 - Laravel依存
class Group extends Model  // Model はLaravel固有
{
    protected $fillable = ['name'];  // Eloquent固有
}
```

#### ValueObject実装ルール
```php
// ✅ 良い例
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
        if (mb_strlen($value) > 100) {
            throw new InvalidValueException('名前は100文字以内で入力してください');
        }
    }
}
```

#### DomainService実装ルール
```php
// ✅ 良い例
class GroupDomainService
{
    public function __construct(
        private readonly GroupRepositoryInterface $groupRepository,
        private readonly UuidGeneratorInterface $uuidGenerator
    ) {}

    public function createGroup(Name $name): Group
    {
        $groupId = new GroupId($this->uuidGenerator->generate());
        $shareToken = new ShareToken($this->uuidGenerator->generate());
        
        $group = new Group($groupId, $name, $shareToken, now(), now());
        
        $this->groupRepository->save($group);
        
        return $group;
    }
}
```

### 2. Application層 (`backend/app/UseCase/`)

#### CQRS実装（Command/Query分離）

**UseCase（更新系）**
```php
class CreateGroupUseCase
{
    public function __construct(
        private readonly GroupDomainService $groupDomainService,
        private readonly TransactionManagerInterface $transactionManager,
        private readonly BusinessEventLogger $eventLogger
    ) {}

    public function execute(CreateGroupCommand $command): CreateGroupResponse
    {
        return $this->transactionManager->transaction(function () use ($command) {
            $group = $this->groupDomainService->createGroup($command->name);
            
            // ビジネスイベントログ
            $this->eventLogger->logGroupCreated(
                $group->getId()->value(),
                $group->getName()->value()
            );
            
            return new CreateGroupResponse(
                groupId: $group->getId()->value(),
                name: $group->getName()->value(),
                shareToken: $group->getShareToken()->value(),
                createdAt: $group->getCreatedAt()
            );
        });
    }
}
```

**QueryService（参照系）**
```php
class GetGroupQueryService
{
    public function __construct(
        private readonly GroupRepositoryInterface $groupRepository,
        private readonly ChildrenRepositoryInterface $childrenRepository
    ) {}

    public function getByShareToken(GetGroupByTokenQuery $query): ?GetGroupResponse
    {
        $group = $this->groupRepository->findByShareToken($query->shareToken);
        
        if (!$group) {
            return null;
        }

        $children = $this->childrenRepository->findByGroupId($group->getId());

        return new GetGroupResponse(
            groupId: $group->getId()->value(),
            name: $group->getName()->value(),
            shareToken: $group->getShareToken()->value(),
            children: array_map(fn($child) => new ChildSummaryDTO(
                childId: $child->getId()->value(),
                name: $child->getName()->value()
            ), $children),
            createdAt: $group->getCreatedAt()
        );
    }
}
```

#### DTO実装ルール
```php
// Command DTO（入力）
readonly class CreateGroupCommand
{
    public function __construct(
        public Name $name
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            name: new Name($data['name'])
        );
    }
}

// Response DTO（出力）
readonly class CreateGroupResponse extends ResponseDTO
{
    public function __construct(
        public string $groupId,
        public string $name,
        public string $shareToken,
        public ?Carbon $createdAt = null
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->groupId,
            'name' => $this->name,
            'share_token' => $this->shareToken,
            'created_at' => $this->createdAt?->toISOString(),
        ];
    }
}
```

### 3. Infrastructure層 (`backend/app/infra/`)

#### Repository実装ルール
```php
class EloquentGroupRepository implements GroupRepositoryInterface
{
    public function __construct(
        private readonly UserGroupModelInterface $userGroupModel,
        private readonly EloquentToDomainConverter $converter
    ) {}

    public function findById(GroupId $groupId): ?Group
    {
        $model = $this->userGroupModel->find($groupId->value());
        
        return $model ? $this->converter->convertGroup($model) : null;
    }

    public function save(Group $group): void
    {
        $data = $this->converter->convertGroupToArray($group);
        
        if ($group->getId()) {
            $this->userGroupModel->update($group->getId()->value(), $data);
        } else {
            $this->userGroupModel->create($data);
        }
    }
}
```

#### ModelAdapter実装ルール
```php
class UserGroupModelAdapter implements UserGroupModelInterface
{
    public function __construct(private readonly UserGroup $model) {}

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
}
```

### 4. Presentation層 (`backend/app/Http/Controllers/`)

#### Controller薄化ルール
```php
class CreateGroupController extends Controller
{
    public function __construct(
        private readonly CreateGroupUseCase $createGroupUseCase,
        private readonly GroupExceptionMapper $exceptionMapper
    ) {}

    public function __invoke(CreateGroupRequest $request): JsonResponse
    {
        try {
            $command = CreateGroupCommand::fromRequest($request->validated());
            $response = $this->createGroupUseCase->execute($command);
            
            return $this->successResponse(
                'グループが正常に作成されました',
                $response,
                Response::HTTP_CREATED
            );
            
        } catch (DomainException $e) {
            return $this->handleDomainException($e, $this->exceptionMapper);
        }
    }
}
```

## エラーハンドリング

### 例外マッピング表

| ドメイン例外 | HTTPステータス | エラーコード | 用途 |
|-------------|---------------|-------------|------|
| BusinessRuleViolationException | 409 Conflict | BUSINESS_RULE_VIOLATION | ビジネスルール違反 |
| GroupNotFoundException | 404 Not Found | GROUP_NOT_FOUND | グループ未検出 |
| ChildNotFoundException | 404 Not Found | CHILD_NOT_FOUND | 子ども未検出 |
| DuplicateChildNameException | 409 Conflict | DUPLICATE_CHILD_NAME | 名前重複 |
| InsufficientStockException | 409 Conflict | INSUFFICIENT_STOCK | 在庫不足 |
| InvalidValueException | 422 Unprocessable Entity | INVALID_VALUE | 入力値不正 |

### 統一エラーレスポンス
```json
{
  "success": false,
  "message": "ユーザーフレンドリーなメッセージ",
  "error_code": "BUSINESS_RULE_VIOLATION",
  "trace_id": "trace_674a1b2c3d4e5f",
  "data": null
}
```

## DI設定

### ServiceProviderでのバインド
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

        // モデルアダプター
        $this->app->bind(UserGroupModelInterface::class, UserGroupModelAdapter::class);
        
        // UseCase・QueryService
        $this->app->bind(CreateGroupUseCase::class);
        $this->app->bind(GetGroupQueryService::class);
    }
}
```

## テスト戦略

### Domain層（ユニットテスト）
- in-memory Repository使用
- Fake Clock使用
- 純粋なビジネスロジックテスト

### Application層（統合テスト）
- トランザクション含むユースケーステスト
- Repository実装使用

### Infrastructure層（Repository実装テスト）
- 実データベース使用
- Eloquent動作確認

### Repository契約テスト
- interface実装が差し替え可能であることを保証
- EloquentRepository・InMemoryRepository両方で実行

## 段階的移行戦略

### Stranglerパターン
1. 新アーキテクチャ実装
2. 既存Controllerを薄化してUseCase呼び出しに変更
3. レスポンス形式の互換性維持
4. 段階的に機能単位で移行

### Feature Flag（オプション）
```php
if (config('app.use_clean_architecture', false)) {
    return $this->executeWithUseCase($request);
} else {
    return $this->executeLegacy($request);
}
```

## 運用・監視

### 構造化ログ
```php
Log::channel('business_events')->info('Group created', [
    'event_type' => 'group_created',
    'group_id' => $groupId,
    'group_name' => $groupName,
    'timestamp' => now()->toISOString(),
]);
```

### ヘルスチェック
- `/api/health` エンドポイント
- データベース接続確認
- Repository動作確認

### パフォーマンス監視
- レスポンス時間500ms以下
- N+1クエリ回避
- メモリ使用量監視

## 保守・開発指針

### 開発時の注意点
1. **Domain層の純粋性保持**: Laravel依存の完全排除
2. **CQRS原則遵守**: 読み書き操作の明確な分離
3. **Value Object活用**: 生のstring/intではなくVOを使用
4. **トランザクション境界**: App層でのみ管理
5. **エラーハンドリング統一**: 例外マッピング表に従う

### レビューポイント
- [ ] Domain層にLaravel依存がないか
- [ ] UseCase/QueryServiceで適切にCQRSが実装されているか
- [ ] Value Objectが適切に使用されているか
- [ ] エラーハンドリングが統一されているか
- [ ] テストが適切なレベルで書かれているか

### 新機能開発手順
1. Domain層でEntity・ValueObject・Repository interface定義
2. DomainServiceでビジネスロジック実装
3. Infrastructure層でRepository実装
4. Application層でUseCase・QueryService実装
5. Presentation層でController薄化実装
6. 各層のテスト作成

この指針に従うことで、保守性が高く、テストしやすく、技術的負債の少ないコードベースを維持できます。