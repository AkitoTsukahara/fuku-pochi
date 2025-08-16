# Task 029: Children UseCase・QueryService・DTO作成

## 概要
ChildrenコンテキストのApplication層を構築し、UseCase（Command）・QueryService・DTOを実装してController層との橋渡しを行う。

## 目標
- Children管理のCQRS実装
- グループとの関連性を考慮したビジネスロジック実行
- 適切なトランザクション管理とエラーハンドリング

## 作業内容

### 1. Children UseCase（Command）作成

#### 1.1 CreateChildUseCase
- `UseCase/Children/Command/CreateChildUseCase.php`
```php
class CreateChildUseCase
{
    public function __construct(
        private readonly ChildrenDomainService $childrenDomainService,
        private readonly ValidationDomainService $validationDomainService,
        private readonly TransactionManagerInterface $transactionManager
    ) {}

    public function execute(CreateChildCommand $command): CreateChildResponse
    {
        return $this->transactionManager->transaction(function () use ($command) {
            // グループ存在チェック
            $this->validationDomainService->ensureGroupExists($command->groupId);
            
            // 子ども作成
            $child = $this->childrenDomainService->createChild(
                $command->groupId,
                $command->name
            );
            
            return new CreateChildResponse(
                childId: $child->getId()->value(),
                groupId: $child->getGroupId()->value(),
                name: $child->getName()->value(),
                createdAt: $child->getCreatedAt()
            );
        });
    }
}
```

#### 1.2 UpdateChildUseCase
- `UseCase/Children/Command/UpdateChildUseCase.php`
```php
class UpdateChildUseCase
{
    public function execute(UpdateChildCommand $command): UpdateChildResponse
    {
        return $this->transactionManager->transaction(function () use ($command) {
            $child = $this->childrenRepository->findById($command->childId);
            
            if (!$child) {
                throw new ChildNotFoundException();
            }

            // 同一グループ内での名前重複チェック
            if ($this->childrenDomainService->isDuplicateName(
                $child->getGroupId(),
                $command->name,
                $command->childId
            )) {
                throw new DuplicateChildNameException();
            }

            $child->changeName($command->name);
            $this->childrenRepository->save($child);
            
            return new UpdateChildResponse(
                childId: $child->getId()->value(),
                name: $child->getName()->value(),
                updatedAt: $child->getUpdatedAt()
            );
        });
    }
}
```

#### 1.3 DeleteChildUseCase
- `UseCase/Children/Command/DeleteChildUseCase.php`
```php
class DeleteChildUseCase
{
    public function execute(DeleteChildCommand $command): DeleteChildResponse
    {
        return $this->transactionManager->transaction(function () use ($command) {
            // 削除可能チェック（在庫データとの整合性）
            if (!$this->childrenDomainService->canDeleteChild($command->childId)) {
                throw new CannotDeleteChildException('関連する在庫データが存在します');
            }

            $this->childrenRepository->delete($command->childId);
            
            return new DeleteChildResponse(
                childId: $command->childId->value(),
                deletedAt: new Carbon()
            );
        });
    }
}
```

### 2. Children QueryService作成

#### 2.1 GetChildrenQueryService
- `UseCase/Children/Query/GetChildrenQueryService.php`
```php
class GetChildrenQueryService
{
    public function __construct(
        private readonly ChildrenRepositoryInterface $childrenRepository,
        private readonly GroupRepositoryInterface $groupRepository,
        private readonly StockRepositoryInterface $stockRepository
    ) {}

    public function getByGroupToken(GetChildrenByGroupTokenQuery $query): GetChildrenResponse
    {
        $group = $this->groupRepository->findByShareToken($query->shareToken);
        
        if (!$group) {
            throw new GroupNotFoundException();
        }

        $children = $this->childrenRepository->findByGroupId($group->getId());

        return new GetChildrenResponse(
            groupId: $group->getId()->value(),
            groupName: $group->getName()->value(),
            children: array_map(fn($child) => new ChildDetailDTO(
                childId: $child->getId()->value(),
                name: $child->getName()->value(),
                stockItemCount: $this->countStockItems($child->getId()),
                createdAt: $child->getCreatedAt()
            ), $children)
        );
    }

    private function countStockItems(ChildId $childId): int
    {
        return count($this->stockRepository->findByChildId($childId));
    }
}
```

### 3. Command DTO作成

#### 3.1 Create Child Command
- `UseCase/Children/Command/DTO/CreateChildCommand.php`
```php
readonly class CreateChildCommand
{
    public function __construct(
        public GroupId $groupId,
        public Name $name
    ) {}

    public static function fromRequest(string $token, array $data): self
    {
        // 注意: GroupIdはTokenから逆引きしてUseCaseで解決
        return new self(
            groupId: new GroupId($token), // 仮ID、実際はUseCaseでToken→GroupIdに変換
            name: new Name($data['name'])
        );
    }

    public static function fromTokenAndData(GroupId $groupId, array $data): self
    {
        return new self(
            groupId: $groupId,
            name: new Name($data['name'])
        );
    }
}
```

#### 3.2 Update Child Command
- `UseCase/Children/Command/DTO/UpdateChildCommand.php`
```php
readonly class UpdateChildCommand
{
    public function __construct(
        public ChildId $childId,
        public Name $name
    ) {}

    public static function fromRequest(string $childId, array $data): self
    {
        return new self(
            childId: new ChildId($childId),
            name: new Name($data['name'])
        );
    }
}
```

#### 3.3 Delete Child Command
- `UseCase/Children/Command/DTO/DeleteChildCommand.php`
```php
readonly class DeleteChildCommand
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

### 4. Query DTO作成

#### 4.1 Get Children Query
- `UseCase/Children/Query/DTO/GetChildrenByGroupTokenQuery.php`
```php
readonly class GetChildrenByGroupTokenQuery
{
    public function __construct(
        public ShareToken $shareToken
    ) {}

    public static function fromRequest(string $token): self
    {
        return new self(
            shareToken: new ShareToken($token)
        );
    }
}
```

### 5. Response DTO作成

#### 5.1 Create Child Response
- `UseCase/Children/Command/DTO/CreateChildResponse.php`
```php
readonly class CreateChildResponse extends ResponseDTO
{
    public function __construct(
        public string $childId,
        public string $groupId,
        public string $name,
        public ?Carbon $createdAt = null
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->childId,
            'user_group_id' => $this->groupId,
            'name' => $this->name,
            'created_at' => $this->createdAt?->toISOString(),
        ];
    }
}
```

#### 5.2 Get Children Response
- `UseCase/Children/Query/DTO/GetChildrenResponse.php`
```php
readonly class GetChildrenResponse extends ResponseDTO
{
    public function __construct(
        public string $groupId,
        public string $groupName,
        public array $children
    ) {}

    public function toArray(): array
    {
        return [
            'group_id' => $this->groupId,
            'group_name' => $this->groupName,
            'children' => array_map(fn($child) => $child->toArray(), $this->children),
        ];
    }
}
```

### 6. 詳細DTO作成

#### 6.1 Child Detail DTO
- `UseCase/Children/Query/DTO/ChildDetailDTO.php`
```php
readonly class ChildDetailDTO
{
    public function __construct(
        public string $childId,
        public string $name,
        public int $stockItemCount,
        public ?Carbon $createdAt = null
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->childId,
            'name' => $this->name,
            'stock_item_count' => $this->stockItemCount,
            'created_at' => $this->createdAt?->toISOString(),
        ];
    }
}
```

### 7. Exception Mapping作成

#### 7.1 ChildrenExceptionMapper
- `UseCase/Children/Exception/ChildrenExceptionMapper.php`
```php
class ChildrenExceptionMapper
{
    public function mapToHttpException(DomainException $exception): HttpException
    {
        return match ($exception::class) {
            ChildNotFoundException::class => new NotFoundHttpException($exception->getMessage()),
            DuplicateChildNameException::class => new ConflictHttpException($exception->getMessage()),
            CannotDeleteChildException::class => new ConflictHttpException($exception->getMessage()),
            InvalidValueException::class => new UnprocessableEntityHttpException($exception->getMessage()),
            GroupNotFoundException::class => new NotFoundHttpException('指定されたグループが見つかりません'),
            default => new InternalServerErrorHttpException('Internal server error')
        };
    }
}
```

## 実装ルール

### 7.1 Group Token解決
- CreateChildCommandではToken→GroupIdの変換をUseCaseで実行
- QueryServiceではToken→Groupの解決を含む

### 7.2 関連データ考慮
- 子ども削除時の在庫データとの整合性チェック
- 同一グループ内での名前重複チェック

### 7.3 エラーハンドリング
- ドメイン例外を適切なHTTP例外にマッピング
- ビジネスルール違反の適切な表現

## 受け入れ条件
- [ ] Children関連のUseCaseが作成されている
- [ ] Children関連のQueryServiceが作成されている
- [ ] Command・Query・Response DTOが作成されている
- [ ] Group Tokenからの解決機能が実装されている
- [ ] 関連データとの整合性チェックが実装されている
- [ ] 例外マッピング機能が実装されている
- [ ] トランザクション管理が適切に実装されている

## 参考
- グループと子どもの関連性を適切に管理
- Token経由でのアクセス制御を考慮
- 在庫データとの整合性を保持