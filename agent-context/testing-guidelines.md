# テスト実装ガイドライン

## Featureテスト

### 概要
全てのAPIエンドポイントに対して包括的なFeatureテストを実装し、動作を保証します。

### テスト方針
1. **全エンドポイント対応**: 各APIエンドポイントに専用のテストクラスを作成
2. **包括的テストケース**: 正常系・異常系・バリデーション・セキュリティを網羅
3. **データベース分離**: `RefreshDatabase` トレイトを使用
4. **Factory活用**: テストデータ生成にFactoryを使用

### ディレクトリ構造
```
tests/Feature/
├── Groups/
│   ├── CreateGroupControllerTest.php
│   └── GetGroupControllerTest.php
└── Children/
    ├── GetChildrenControllerTest.php
    ├── CreateChildControllerTest.php
    ├── UpdateChildControllerTest.php
    └── DeleteChildControllerTest.php
```

### テストクラス基本構造

#### 基本テンプレート
```php
<?php

namespace Tests\Feature\Groups;

use App\Models\UserGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateGroupControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_group_with_valid_data(): void
    {
        $response = $this->postJson('/api/groups', [
            'name' => 'テストグループ'
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'グループが正常に作成されました',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'share_token',
                    'children',
                    'created_at',
                    'updated_at',
                ]
            ]);

        $this->assertDatabaseHas('user_groups', [
            'name' => 'テストグループ'
        ]);
    }

    // その他のテストメソッド...
}
```

### 必須テストケース

#### CREATEエンドポイント
1. **正常系**
   - 有効なデータでリソース作成成功
   - レスポンス形式確認
   - データベース保存確認

2. **バリデーション**
   - 必須フィールドなし（422エラー）
   - 空文字列（422エラー）
   - 文字数制限超過（422エラー）
   - 不正な型（422エラー）

3. **エラー系**
   - 存在しない親リソース（404エラー）

#### GETエンドポイント
1. **正常系**
   - 有効なIDでリソース取得成功
   - レスポンス形式確認
   - 関連データ含む取得

2. **エラー系**
   - 存在しないID（404エラー）
   - 不正なトークン（404エラー）

3. **セキュリティ**
   - 他グループのデータアクセス制限

#### UPDATEエンドポイント
1. **正常系**
   - 有効なデータで更新成功
   - タイムスタンプ更新確認
   - 不変フィールド確認

2. **バリデーション**
   - 必須フィールドなし（422エラー）
   - 文字数制限超過（422エラー）

3. **エラー系**
   - 存在しないID（404エラー）

#### DELETEエンドポイント
1. **正常系**
   - リソース削除成功
   - データベースから削除確認

2. **エラー系**
   - 存在しないID（404エラー）
   - 冪等性確認

3. **セキュリティ**
   - 他グループのデータに影響しない

### テストケース例

#### 正常系テスト
```php
public function test_can_create_child_with_valid_data(): void
{
    $group = UserGroup::factory()->create();

    $response = $this->postJson("/api/groups/{$group->share_token}/children", [
        'name' => '太郎'
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => '子どもが正常に登録されました',
        ]);

    $this->assertDatabaseHas('children', [
        'user_group_id' => $group->id,
        'name' => '太郎'
    ]);
}
```

#### バリデーションテスト
```php
public function test_cannot_create_child_without_name(): void
{
    $group = UserGroup::factory()->create();

    $response = $this->postJson("/api/groups/{$group->share_token}/children", []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name'])
        ->assertJson([
            'message' => '子どもの名前は必須です',
            'errors' => [
                'name' => ['子どもの名前は必須です']
            ]
        ]);

    $this->assertDatabaseCount('children', 0);
}
```

#### セキュリティテスト
```php
public function test_does_not_return_children_from_other_groups(): void
{
    $group1 = UserGroup::factory()->create(['name' => 'グループ1']);
    $group2 = UserGroup::factory()->create(['name' => 'グループ2']);

    $child1 = Children::factory()->create([
        'user_group_id' => $group1->id,
        'name' => 'グループ1の子'
    ]);

    $child2 = Children::factory()->create([
        'user_group_id' => $group2->id,
        'name' => 'グループ2の子'
    ]);

    $response = $this->getJson("/api/groups/{$group1->share_token}/children");

    $response->assertStatus(200);
    $responseData = $response->json('data');
    $this->assertCount(1, $responseData);
    $this->assertEquals('グループ1の子', $responseData[0]['name']);
}
```

### Factory使用

#### モデルファクトリー
```php
// UserGroupFactory
class UserGroupFactory extends Factory
{
    protected $model = UserGroup::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'share_token' => Str::random(32),
        ];
    }
}

// ChildrenFactory
class ChildrenFactory extends Factory
{
    protected $model = Children::class;

    public function definition(): array
    {
        return [
            'user_group_id' => UserGroup::factory(),
            'name' => $this->faker->firstName(),
        ];
    }
}
```

#### モデルでのFactory有効化
```php
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserGroup extends Model
{
    use HasFactory;
    // ...
}
```

### アサーション例

#### JSONアサーション
```php
// 完全一致
$response->assertJson([
    'success' => true,
    'message' => 'メッセージ'
]);

// 構造確認
$response->assertJsonStructure([
    'success',
    'message',
    'data' => [
        'id',
        'name',
        'created_at',
        'updated_at'
    ]
]);

// バリデーションエラー
$response->assertJsonValidationErrors(['name']);
```

#### データベースアサーション
```php
// レコード存在確認
$this->assertDatabaseHas('table_name', [
    'column' => 'value'
]);

// レコード削除確認
$this->assertDatabaseMissing('table_name', [
    'id' => $id
]);

// レコード数確認
$this->assertDatabaseCount('table_name', 2);
```

### テスト実行

#### 特定テスト実行
```bash
# 特定のテストクラス
php artisan test tests/Feature/Groups/CreateGroupControllerTest.php

# 特定のテストメソッド
php artisan test --filter test_can_create_group_with_valid_data

# グループ別
php artisan test --filter Groups
php artisan test --filter Children
```

#### 全テスト実行
```bash
# 全Featureテスト
php artisan test tests/Feature/

# 全テスト
php artisan test
```

### 命名規則

#### テストメソッド名
- `test_can_{action}_with_valid_data` - 正常系
- `test_cannot_{action}_without_{field}` - バリデーション
- `test_returns_404_for_non_existent_{resource}` - エラー系
- `test_{security_aspect}` - セキュリティ

#### テストクラス名
- `{Controller}Test` の形式
- 例: `CreateGroupControllerTest`, `GetChildrenControllerTest`

### 品質指標
- **全エンドポイント対応**: 漏れなくテスト実装
- **高いカバレッジ**: 正常系・異常系を網羅
- **テスト成功率**: 100%を維持
- **実行速度**: 高速なテスト実行

## クリーンアーキテクチャでのテスト戦略（2025年リファクタリング後）

### テスト階層と責務

#### 1. Domain層テスト（ユニットテスト）
**対象**: Entity, ValueObject, DomainService  
**環境**: In-memory Repository, Fake Clock  
**目的**: ビジネスロジックの純粋性確認

```php
class GroupDomainServiceTest extends TestCase
{
    private GroupDomainService $service;
    private InMemoryGroupRepository $repository;
    private FakeUuidGenerator $uuidGenerator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new InMemoryGroupRepository();
        $this->uuidGenerator = new FakeUuidGenerator();
        $this->service = new GroupDomainService($this->repository, $this->uuidGenerator);
    }

    public function test_can_create_group_with_valid_name(): void
    {
        $name = new Name('テストグループ');
        
        $group = $this->service->createGroup($name);
        
        $this->assertEquals('テストグループ', $group->getName()->value());
        $this->assertNotNull($group->getShareToken());
    }

    public function test_cannot_create_group_with_empty_name(): void
    {
        $this->expectException(InvalidValueException::class);
        
        new Name('');
    }
}
```

#### 2. Application層テスト（統合テスト）
**対象**: UseCase, QueryService  
**環境**: トランザクション含む実Repository  
**目的**: ユースケースの結合動作確認

```php
class CreateGroupUseCaseTest extends TestCase
{
    use RefreshDatabase;

    private CreateGroupUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useCase = app(CreateGroupUseCase::class);
    }

    public function test_can_execute_create_group_use_case(): void
    {
        $command = new CreateGroupCommand(new Name('統合テストグループ'));
        
        $response = $this->useCase->execute($command);
        
        $this->assertEquals('統合テストグループ', $response->name);
        $this->assertDatabaseHas('user_groups', [
            'name' => '統合テストグループ'
        ]);
    }

    public function test_transaction_rollback_on_domain_exception(): void
    {
        // ドメイン例外が発生した場合のトランザクションロールバック確認
        $this->expectException(BusinessRuleViolationException::class);
        
        $command = new CreateGroupCommand(new Name(''));
        $this->useCase->execute($command);
        
        $this->assertDatabaseCount('user_groups', 0);
    }
}
```

#### 3. Infrastructure層テスト（Repository実装テスト）
**対象**: EloquentRepository, ModelAdapter  
**環境**: 実データベース  
**目的**: データ永続化の動作確認

```php
class EloquentGroupRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentGroupRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(EloquentGroupRepository::class);
    }

    public function test_can_save_and_find_group(): void
    {
        $group = new Group(
            new GroupId('test-group-id'),
            new Name('リポジトリテスト'),
            new ShareToken('test-token'),
            now(),
            now()
        );

        $this->repository->save($group);
        $foundGroup = $this->repository->findById(new GroupId('test-group-id'));

        $this->assertNotNull($foundGroup);
        $this->assertEquals('リポジトリテスト', $foundGroup->getName()->value());
    }

    public function test_eloquent_to_domain_conversion(): void
    {
        $userGroup = UserGroup::factory()->create([
            'name' => 'Eloquent変換テスト'
        ]);

        $group = $this->repository->findById(new GroupId($userGroup->id));

        $this->assertInstanceOf(Group::class, $group);
        $this->assertEquals('Eloquent変換テスト', $group->getName()->value());
    }
}
```

#### 4. Repository契約テスト
**対象**: Repository interface実装  
**環境**: 複数実装（Eloquent, InMemory）  
**目的**: 実装差し替え可能性の保証

```php
abstract class GroupRepositoryContractTest extends TestCase
{
    protected GroupRepositoryInterface $repository;

    abstract protected function getRepositoryImplementation(): GroupRepositoryInterface;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->getRepositoryImplementation();
    }

    public function test_can_save_and_find_by_id(): void
    {
        $group = $this->createTestGroup();
        
        $this->repository->save($group);
        $foundGroup = $this->repository->findById($group->getId());
        
        $this->assertNotNull($foundGroup);
        $this->assertEquals($group->getName()->value(), $foundGroup->getName()->value());
    }

    private function createTestGroup(): Group
    {
        return new Group(
            new GroupId('contract-test-id'),
            new Name('契約テスト'),
            new ShareToken('contract-token'),
            now(),
            now()
        );
    }
}

class EloquentGroupRepositoryContractTest extends GroupRepositoryContractTest
{
    use RefreshDatabase;

    protected function getRepositoryImplementation(): GroupRepositoryInterface
    {
        return app(EloquentGroupRepository::class);
    }
}

class InMemoryGroupRepositoryContractTest extends GroupRepositoryContractTest
{
    protected function getRepositoryImplementation(): GroupRepositoryInterface
    {
        return new InMemoryGroupRepository();
    }
}
```

#### 5. パフォーマンステスト
**対象**: APIエンドポイント  
**環境**: 実環境に近い状態  
**目的**: レスポンス時間・クエリ数の監視

```php
class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_response_time_under_threshold(): void
    {
        $userGroup = UserGroup::factory()->create();
        $children = Children::factory()->count(10)->create(['user_group_id' => $userGroup->id]);
        
        $startTime = microtime(true);
        
        $response = $this->getJson("/api/groups/{$userGroup->share_token}");
        
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000; // milliseconds

        $response->assertStatus(200);
        $this->assertLessThan(500, $responseTime, 'API response time should be under 500ms');
    }

    public function test_avoid_n_plus_one_queries(): void
    {
        $userGroup = UserGroup::factory()->create();
        $children = Children::factory()->count(20)->create(['user_group_id' => $userGroup->id]);

        DB::enableQueryLog();
        
        $response = $this->getJson("/api/groups/{$userGroup->share_token}");
        
        $queryCount = count(DB::getQueryLog());
        
        $response->assertStatus(200);
        $this->assertLessThan(5, $queryCount, 'Should avoid N+1 queries');
    }
}
```

### テストディレクトリ構造（更新版）

```
tests/
├── Unit/                           # Domain層ユニットテスト
│   ├── Domain/
│   │   ├── Group/
│   │   │   ├── Entity/
│   │   │   ├── ValueObject/
│   │   │   └── Service/
│   │   ├── Children/
│   │   └── Stock/
│   └── Exception/
├── Integration/                    # Application層統合テスト
│   ├── UseCase/
│   │   ├── Group/
│   │   ├── Children/
│   │   └── Stock/
│   └── QueryService/
├── Infrastructure/                 # Infrastructure層テスト
│   ├── Repository/
│   │   ├── EloquentGroupRepositoryTest.php
│   │   ├── EloquentChildrenRepositoryTest.php
│   │   └── EloquentStockRepositoryTest.php
│   └── Adapter/
├── Contract/                       # Repository契約テスト
│   └── Repository/
│       ├── GroupRepositoryContractTest.php
│       ├── ChildrenRepositoryContractTest.php
│       └── StockRepositoryContractTest.php
├── Performance/                    # パフォーマンステスト
│   ├── ApiPerformanceTest.php
│   └── QueryPerformanceTest.php
└── Feature/                        # APIエンドポイントテスト（既存）
    ├── Groups/
    ├── Children/
    └── Stock/
```

### テスト実行戦略

#### レベル別実行
```bash
# Domain層ユニットテスト（高速）
php artisan test tests/Unit/Domain/

# Application層統合テスト（中速）
php artisan test tests/Integration/

# Infrastructure層テスト（低速）
php artisan test tests/Infrastructure/

# Repository契約テスト（確実性）
php artisan test tests/Contract/

# パフォーマンステスト（監視）
php artisan test tests/Performance/

# Feature テスト（回帰防止）
php artisan test tests/Feature/
```

#### CI/CD での実行順序
1. **Unit tests** - 高速フィードバック
2. **Integration tests** - ユースケース確認
3. **Contract tests** - 実装互換性確認
4. **Feature tests** - API動作確認
5. **Performance tests** - 性能監視

### Mock・Fake実装

#### テスト用In-Memory Repository
```php
class InMemoryGroupRepository implements GroupRepositoryInterface
{
    private array $groups = [];

    public function save(Group $group): void
    {
        $this->groups[$group->getId()->value()] = $group;
    }

    public function findById(GroupId $groupId): ?Group
    {
        return $this->groups[$groupId->value()] ?? null;
    }
}
```

#### テスト用Fake Clock
```php
class FakeClock implements ClockInterface
{
    private Carbon $fixedTime;

    public function __construct(Carbon $fixedTime)
    {
        $this->fixedTime = $fixedTime;
    }

    public function now(): Carbon
    {
        return $this->fixedTime;
    }
}
```

### 品質指標（更新版）

#### カバレッジ目標
- **Domain層**: 95%以上（ビジネスロジック）
- **Application層**: 90%以上（ユースケース）
- **Infrastructure層**: 80%以上（データアクセス）
- **Controller層**: 85%以上（エンドポイント）

#### パフォーマンス目標
- **API レスポンス時間**: 500ms以下
- **データベースクエリ数**: エンドポイント当たり5回以下
- **メモリ使用量**: リクエスト当たり32MB以下

### 今後の方針（更新版）
1. **層別テスト**: 各層の責務に応じた適切なテスト実装
2. **契約テスト**: Repository実装差し替え可能性の保証
3. **パフォーマンス監視**: 継続的な性能測定
4. **テストピラミッド**: Unit > Integration > Feature の適切な比率維持
5. **CI/CD統合**: 段階的テスト実行による効率的フィードバック