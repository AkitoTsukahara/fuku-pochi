# Task 035: ログ監視・契約テスト追加

## 概要
クリーンアーキテクチャリファクタリングの最終仕上げとして、運用監視体制とRepository契約テストを構築し、長期的な保守性を確保する。

## 目標
- Repository契約テストによる実装差し替え可能性の担保
- アプリケーション監視とアラート体制の構築
- パフォーマンス監視とボトルネック検出

## 作業内容

### 1. Repository契約テスト作成

#### 1.1 Repository契約テスト基盤
- `tests/Contract/Repository/RepositoryContractTest.php`
```php
abstract class RepositoryContractTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Repository実装差し替え時にも動作することを保証する契約テスト
     */
    abstract protected function getRepositoryImplementation(): mixed;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->getRepositoryImplementation();
    }
}
```

#### 1.2 GroupRepository契約テスト
- `tests/Contract/Repository/GroupRepositoryContractTest.php`
```php
class GroupRepositoryContractTest extends RepositoryContractTest
{
    protected function getRepositoryImplementation(): GroupRepositoryInterface
    {
        return app(GroupRepositoryInterface::class);
    }

    public function test_save_and_find_by_id()
    {
        $group = new Group(
            new GroupId('test-group-id'),
            new Name('テストグループ'),
            new ShareToken('test-token-123'),
            now(),
            now()
        );

        $this->repository->save($group);
        $foundGroup = $this->repository->findById(new GroupId('test-group-id'));

        $this->assertNotNull($foundGroup);
        $this->assertEquals('テストグループ', $foundGroup->getName()->value());
    }

    public function test_find_by_share_token()
    {
        $group = new Group(
            new GroupId('test-group-id'),
            new Name('テストグループ'),
            new ShareToken('unique-token'),
            now(),
            now()
        );

        $this->repository->save($group);
        $foundGroup = $this->repository->findByShareToken(new ShareToken('unique-token'));

        $this->assertNotNull($foundGroup);
        $this->assertEquals('test-group-id', $foundGroup->getId()->value());
    }

    public function test_delete()
    {
        $groupId = new GroupId('test-group-id');
        $group = new Group(
            $groupId,
            new Name('削除テスト'),
            new ShareToken('delete-token'),
            now(),
            now()
        );

        $this->repository->save($group);
        $this->repository->delete($groupId);
        
        $this->assertNull($this->repository->findById($groupId));
    }
}
```

#### 1.3 その他Repository契約テスト
- `tests/Contract/Repository/ChildrenRepositoryContractTest.php`
- `tests/Contract/Repository/StockRepositoryContractTest.php`
- `tests/Contract/Repository/ClothingCategoryRepositoryContractTest.php`

### 2. インメモリRepository実装（テスト用）

#### 2.1 InMemoryGroupRepository
- `tests/Fixtures/Repository/InMemoryGroupRepository.php`
```php
class InMemoryGroupRepository implements GroupRepositoryInterface
{
    private array $groups = [];

    public function findById(GroupId $groupId): ?Group
    {
        return $this->groups[$groupId->value()] ?? null;
    }

    public function findByShareToken(ShareToken $token): ?Group
    {
        foreach ($this->groups as $group) {
            if ($group->getShareToken()->equals($token)) {
                return $group;
            }
        }
        return null;
    }

    public function save(Group $group): void
    {
        $this->groups[$group->getId()->value()] = $group;
    }

    public function delete(GroupId $groupId): void
    {
        unset($this->groups[$groupId->value()]);
    }
}
```

### 3. パフォーマンス監視テスト

#### 3.1 PerformanceMonitoringTest
- `tests/Performance/PerformanceMonitoringTest.php`
```php
class PerformanceMonitoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_response_time_under_threshold()
    {
        $userGroup = UserGroup::factory()->create();
        
        $startTime = microtime(true);
        
        $response = $this->getJson("/api/groups/{$userGroup->share_token}");
        
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000; // milliseconds

        $response->assertStatus(200);
        $this->assertLessThan(500, $responseTime, 'API response time should be under 500ms');
    }

    public function test_database_query_count()
    {
        $userGroup = UserGroup::factory()->create();
        $children = Children::factory()->count(5)->create(['user_group_id' => $userGroup->id]);

        DB::enableQueryLog();
        
        $response = $this->getJson("/api/groups/{$userGroup->share_token}");
        
        $queryCount = count(DB::getQueryLog());
        
        $response->assertStatus(200);
        $this->assertLessThan(5, $queryCount, 'Should avoid N+1 queries');
    }
}
```

### 4. ヘルスチェックエンドポイント

#### 4.1 HealthCheckController
- `app/Http/Controllers/Api/HealthCheckController.php`
```php
class HealthCheckController extends Controller
{
    public function __construct(
        private readonly GroupRepositoryInterface $groupRepository,
        private readonly ChildrenRepositoryInterface $childrenRepository,
        private readonly StockRepositoryInterface $stockRepository
    ) {}

    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'repositories' => $this->checkRepositories(),
            'storage' => $this->checkStorage(),
        ];

        $allHealthy = !in_array(false, $checks, true);

        return response()->json([
            'status' => $allHealthy ? 'healthy' : 'unhealthy',
            'checks' => $checks,
            'timestamp' => now()->toISOString(),
        ], $allHealthy ? 200 : 503);
    }

    private function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (Exception $e) {
            Log::error('Database health check failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    private function checkRepositories(): bool
    {
        try {
            // Repository基本動作確認
            $this->groupRepository->findById(new GroupId('health-check-test'));
            return true;
        } catch (Exception $e) {
            Log::error('Repository health check failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    private function checkStorage(): bool
    {
        try {
            $testFile = storage_path('app/health-check.txt');
            file_put_contents($testFile, 'health check');
            $content = file_get_contents($testFile);
            unlink($testFile);
            return $content === 'health check';
        } catch (Exception $e) {
            Log::error('Storage health check failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
```

### 5. アプリケーションメトリクス

#### 5.1 MetricsCollector
- `app/Services/Monitoring/MetricsCollector.php`
```php
class MetricsCollector
{
    public function collectApiMetrics(string $endpoint, float $responseTime, int $statusCode): void
    {
        Log::channel('metrics')->info('API request completed', [
            'metric_type' => 'api_request',
            'endpoint' => $endpoint,
            'response_time_ms' => round($responseTime * 1000, 2),
            'status_code' => $statusCode,
            'timestamp' => now()->toISOString(),
        ]);
    }

    public function collectBusinessMetrics(string $eventType, array $data): void
    {
        Log::channel('metrics')->info('Business event occurred', [
            'metric_type' => 'business_event',
            'event_type' => $eventType,
            'data' => $data,
            'timestamp' => now()->toISOString(),
        ]);
    }

    public function collectErrorMetrics(string $errorType, string $errorCode, array $context): void
    {
        Log::channel('metrics')->warning('Error occurred', [
            'metric_type' => 'error',
            'error_type' => $errorType,
            'error_code' => $errorCode,
            'context' => $context,
            'timestamp' => now()->toISOString(),
        ]);
    }
}
```

### 6. ミドルウェア監視

#### 6.1 ApiMonitoringMiddleware
- `app/Http/Middleware/ApiMonitoringMiddleware.php`
```php
class ApiMonitoringMiddleware
{
    public function __construct(
        private readonly MetricsCollector $metricsCollector
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        
        $response = $next($request);
        
        $endTime = microtime(true);
        $responseTime = $endTime - $startTime;

        $this->metricsCollector->collectApiMetrics(
            $request->getPathInfo(),
            $responseTime,
            $response->getStatusCode()
        );

        return $response;
    }
}
```

### 7. ログ分析用設定

#### 7.1 メトリクス用ログチャンネル
- `config/logging.php`追加
```php
'metrics' => [
    'driver' => 'daily',
    'path' => storage_path('logs/metrics.log'),
    'level' => 'info',
    'days' => 90,
    'formatter' => Monolog\Formatter\JsonFormatter::class,
],
```

### 8. アラート監視スクリプト

#### 8.1 エラー率監視
- `scripts/monitoring/check_error_rate.php`
```php
#!/usr/bin/env php
<?php

// 過去5分間のエラー率をチェック
$logFile = storage_path('logs/laravel-' . date('Y-m-d') . '.log');
$fiveMinutesAgo = time() - 300;

$totalRequests = 0;
$errorRequests = 0;

if (file_exists($logFile)) {
    $lines = file($logFile);
    foreach ($lines as $line) {
        $logData = json_decode($line, true);
        if ($logData && isset($logData['timestamp'])) {
            $logTime = strtotime($logData['timestamp']);
            if ($logTime >= $fiveMinutesAgo) {
                if (isset($logData['metric_type']) && $logData['metric_type'] === 'api_request') {
                    $totalRequests++;
                    if ($logData['status_code'] >= 400) {
                        $errorRequests++;
                    }
                }
            }
        }
    }
}

$errorRate = $totalRequests > 0 ? ($errorRequests / $totalRequests) * 100 : 0;

if ($errorRate > 10) { // 10%以上のエラー率でアラート
    echo "ALERT: Error rate is {$errorRate}% in the last 5 minutes\n";
    exit(1);
} else {
    echo "OK: Error rate is {$errorRate}%\n";
    exit(0);
}
```

### 9. 統合テストスイート

#### 9.1 CleanArchitectureIntegrationTest
- `tests/Integration/CleanArchitectureIntegrationTest.php`
```php
class CleanArchitectureIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_group_and_children_workflow()
    {
        // 1. グループ作成
        $createGroupResponse = $this->postJson('/api/groups', [
            'name' => 'インテグレーションテスト'
        ]);
        
        $createGroupResponse->assertStatus(201);
        $groupData = $createGroupResponse->json('data');
        
        // 2. 子ども作成
        $createChildResponse = $this->postJson("/api/groups/{$groupData['share_token']}/children", [
            'name' => 'テスト太郎'
        ]);
        
        $createChildResponse->assertStatus(201);
        $childData = $createChildResponse->json('data');
        
        // 3. 在庫操作
        $category = ClothingCategory::factory()->create();
        $incrementResponse = $this->putJson("/api/children/{$childData['id']}/stock/increment", [
            'clothing_category_id' => $category->id,
            'increment' => 3
        ]);
        
        $incrementResponse->assertStatus(200);
        
        // 4. データ整合性確認
        $stockResponse = $this->getJson("/api/children/{$childData['id']}/stock");
        $stockResponse->assertStatus(200);
        
        $stockData = $stockResponse->json('data.stock_items');
        $targetStock = collect($stockData)->firstWhere('clothing_category_id', $category->id);
        
        $this->assertEquals(3, $targetStock['current_count']);
    }
}
```

## 実装ルール

### 9.1 契約テスト原則
- Repository実装が差し替え可能であることを保証
- インメモリ実装でも同じ動作をすること
- 全ての公開メソッドをテストすること

### 9.2 監視原則
- レスポンス時間・エラー率・リソース使用量を監視
- 構造化ログでメトリクス収集
- アラートで異常を早期検出

### 9.3 パフォーマンス原則
- API レスポンス時間500ms以下
- N+1クエリの回避
- データベースクエリ数の最適化

## 受け入れ条件
- [ ] Repository契約テストが全て作成されている
- [ ] インメモリRepository実装が作成されている
- [ ] パフォーマンス監視テストが実装されている
- [ ] ヘルスチェックエンドポイントが動作している
- [ ] アプリケーションメトリクス収集が実装されている
- [ ] ログ分析用設定が完了している
- [ ] アラート監視スクリプトが動作している
- [ ] 統合テストが全て通っている
- [ ] 全契約テストが通っている

## 参考
- Repository契約テストでアーキテクチャの堅牢性確保
- 監視体制で運用時の問題を早期発見
- パフォーマンステストで品質維持

---

## 🎉 クリーンアーキテクチャリファクタリング完了！

このタスクの完了により、以下が達成されます：

### ✅ アーキテクチャ面
- Controller層の薄化とビジネスロジック分離
- Domain層のLaravel非依存化
- CQRS実装による読み書き分離
- Repository パターンによるデータアクセス抽象化

### ✅ 保守性面
- UseCase・DomainServiceによる責務明確化
- Value Objectによる型安全性向上
- 統一的なエラーハンドリング
- 構造化ログによる追跡可能性

### ✅ テスト面
- ユニットテスト・統合テスト・契約テスト
- インメモリ実装によるテスト高速化
- パフォーマンステストによる品質保証

### ✅ 運用面
- ヘルスチェック・メトリクス・アラート
- 監視体制による問題の早期発見
- ログ分析による運用改善

**既存機能を壊すことなく、段階的にクリーンアーキテクチャに移行完了！**