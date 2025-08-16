# Task 028: Group UseCase・QueryService・DTO作成

## 概要
GroupコンテキストのApplication層を構築し、UseCase（Command）・QueryService・DTOを実装してController層との橋渡しを行う。

## 目標
- CQRS（Command Query Responsibility Segregation）の実装
- トランザクション管理をApp層で実施
- DTOでのデータ変換・レスポンス構築

## 作業内容

### 1. Group UseCase（Command）作成

#### 1.1 CreateGroupUseCase
- `UseCase/Group/Command/CreateGroupUseCase.php`
```php
class CreateGroupUseCase
{
    public function __construct(
        private readonly GroupDomainService $groupDomainService,
        private readonly TransactionManagerInterface $transactionManager
    ) {}

    public function execute(CreateGroupCommand $command): CreateGroupResponse
    {
        return $this->transactionManager->transaction(function () use ($command) {
            $group = $this->groupDomainService->createGroup($command->name);
            
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

#### 1.2 RegenerateShareTokenUseCase
- `UseCase/Group/Command/RegenerateShareTokenUseCase.php`
```php
class RegenerateShareTokenUseCase
{
    public function execute(RegenerateShareTokenCommand $command): RegenerateShareTokenResponse;
}
```

### 2. Group QueryService作成

#### 2.1 GetGroupQueryService
- `UseCase/Group/Query/GetGroupQueryService.php`
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

### 3. Command DTO作成

#### 3.1 Create Group Command
- `UseCase/Group/Command/DTO/CreateGroupCommand.php`
```php
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
```

#### 3.2 Regenerate Token Command
- `UseCase/Group/Command/DTO/RegenerateShareTokenCommand.php`
```php
readonly class RegenerateShareTokenCommand
{
    public function __construct(
        public GroupId $groupId
    ) {}
}
```

### 4. Query DTO作成

#### 4.1 Get Group Query
- `UseCase/Group/Query/DTO/GetGroupByTokenQuery.php`
```php
readonly class GetGroupByTokenQuery
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

#### 5.1 Create Group Response
- `UseCase/Group/Command/DTO/CreateGroupResponse.php`
```php
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

#### 5.2 Get Group Response
- `UseCase/Group/Query/DTO/GetGroupResponse.php`
```php
readonly class GetGroupResponse extends ResponseDTO
{
    public function __construct(
        public string $groupId,
        public string $name,
        public string $shareToken,
        public array $children,
        public ?Carbon $createdAt = null
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->groupId,
            'name' => $this->name,
            'share_token' => $this->shareToken,
            'children' => array_map(fn($child) => $child->toArray(), $this->children),
            'created_at' => $this->createdAt?->toISOString(),
        ];
    }
}
```

### 6. 共通DTO作成

#### 6.1 Child Summary DTO
- `UseCase/Shared/DTO/ChildSummaryDTO.php`
```php
readonly class ChildSummaryDTO
{
    public function __construct(
        public string $childId,
        public string $name
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->childId,
            'name' => $this->name,
        ];
    }
}
```

### 7. Exception Mapping作成

#### 7.1 GroupExceptionMapper
- `UseCase/Group/Exception/GroupExceptionMapper.php`
```php
class GroupExceptionMapper
{
    public function mapToHttpException(DomainException $exception): HttpException
    {
        return match ($exception::class) {
            GroupNotFoundException::class => new NotFoundHttpException($exception->getMessage()),
            BusinessRuleViolationException::class => new ConflictHttpException($exception->getMessage()),
            InvalidValueException::class => new UnprocessableEntityHttpException($exception->getMessage()),
            default => new InternalServerErrorHttpException('Internal server error')
        };
    }
}
```

## 実装ルール

### 7.1 UseCase実装ルール
- 1つのUseCaseは1つのユースケースを表現
- トランザクション境界をUseCase内で管理
- DomainServiceを組み合わせてビジネスロジックを実行

### 7.2 QueryService実装ルール
- 読み取り専用、副作用なし
- 複数Repositoryからのデータ取得・結合
- DTOでのレスポンス構築

### 7.3 DTO実装ルール
- readonly クラスで不変性を保証
- FactoryメソッドでHTTPリクエストから構築
- toArray() でレスポンス配列に変換

## 受け入れ条件
- [ ] Group関連のUseCaseが作成されている
- [ ] Group関連のQueryServiceが作成されている
- [ ] Command・Query・Response DTOが作成されている
- [ ] トランザクション管理がUseCase内で実装されている
- [ ] 例外マッピング機能が実装されている
- [ ] CQRS原則に従って読み書きが分離されている
- [ ] Domain層のサービスを適切に利用している

## 参考
- CQRS: Command（更新）とQuery（参照）を分離
- App層でトランザクション境界を管理
- DTOでDomain オブジェクトとHTTPリクエスト/レスポンスを分離