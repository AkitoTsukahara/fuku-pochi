# Task 034: エラーハンドリング統一・例外マッピング

## 概要
ドメイン例外とHTTP例外の統一的なマッピング体系を構築し、一貫性のあるエラーレスポンスとログ出力を実現する。

## 目標
- DomainException→HTTPステータス/エラーコードの統一マッピング
- 構造化ログによるエラー追跡性向上
- ユーザーフレンドリーなエラーメッセージ体系

## 作業内容

### 1. 統一例外マッピング体系

#### 1.1 例外マッピング表作成
- `docs/exception-mapping.md`
```markdown
# ドメイン例外マッピング表

| ドメイン例外 | HTTPステータス | エラーコード | メッセージ例 |
|-------------|---------------|-------------|-------------|
| BusinessRuleViolationException | 409 Conflict | BUSINESS_RULE_VIOLATION | ビジネスルールに違反しています |
| GroupNotFoundException | 404 Not Found | GROUP_NOT_FOUND | 指定されたグループが見つかりません |
| ChildNotFoundException | 404 Not Found | CHILD_NOT_FOUND | 指定された子どもが見つかりません |
| DuplicateChildNameException | 409 Conflict | DUPLICATE_CHILD_NAME | 同じ名前の子どもが既に存在します |
| InsufficientStockException | 409 Conflict | INSUFFICIENT_STOCK | 在庫が不足しています |
| StockLimitExceededException | 422 Unprocessable Entity | STOCK_LIMIT_EXCEEDED | 在庫上限を超えています |
| InvalidValueException | 422 Unprocessable Entity | INVALID_VALUE | 入力値が不正です |
```

#### 1.2 統一ExceptionMapper作成
- `UseCase/Shared/Exception/UnifiedExceptionMapper.php`
```php
class UnifiedExceptionMapper
{
    private const EXCEPTION_MAP = [
        BusinessRuleViolationException::class => ['status' => 409, 'code' => 'BUSINESS_RULE_VIOLATION'],
        GroupNotFoundException::class => ['status' => 404, 'code' => 'GROUP_NOT_FOUND'],
        ChildNotFoundException::class => ['status' => 404, 'code' => 'CHILD_NOT_FOUND'],
        DuplicateChildNameException::class => ['status' => 409, 'code' => 'DUPLICATE_CHILD_NAME'],
        CannotDeleteChildException::class => ['status' => 409, 'code' => 'CANNOT_DELETE_CHILD'],
        InsufficientStockException::class => ['status' => 409, 'code' => 'INSUFFICIENT_STOCK'],
        StockLimitExceededException::class => ['status' => 422, 'code' => 'STOCK_LIMIT_EXCEEDED'],
        InvalidValueException::class => ['status' => 422, 'code' => 'INVALID_VALUE'],
        ClothingCategoryNotFoundException::class => ['status' => 404, 'code' => 'CATEGORY_NOT_FOUND'],
    ];

    public function mapToHttpException(DomainException $exception): array
    {
        $mapping = self::EXCEPTION_MAP[$exception::class] ?? [
            'status' => 500,
            'code' => 'INTERNAL_SERVER_ERROR'
        ];

        return [
            'status' => $mapping['status'],
            'code' => $mapping['code'],
            'message' => $exception->getMessage(),
            'trace_id' => $this->generateTraceId()
        ];
    }

    private function generateTraceId(): string
    {
        return uniqid('trace_', true);
    }
}
```

### 2. エラーレスポンス標準化

#### 2.1 ErrorResponseDTO作成
- `UseCase/Shared/DTO/ErrorResponseDTO.php`
```php
readonly class ErrorResponseDTO
{
    public function __construct(
        public bool $success,
        public string $message,
        public string $errorCode,
        public ?string $traceId = null,
        public ?array $details = null,
        public mixed $data = null
    ) {}

    public function toArray(): array
    {
        $response = [
            'success' => $this->success,
            'message' => $this->message,
            'error_code' => $this->errorCode,
            'data' => $this->data,
        ];

        if ($this->traceId) {
            $response['trace_id'] = $this->traceId;
        }

        if ($this->details) {
            $response['details'] = $this->details;
        }

        return $response;
    }
}
```

#### 2.2 Controller基底クラス強化
- `app/Http/Controllers/Controller.php`更新
```php
abstract class Controller extends BaseController
{
    protected function __construct(
        protected readonly UnifiedExceptionMapper $exceptionMapper
    ) {}

    protected function handleDomainException(DomainException $exception): JsonResponse
    {
        $mapping = $this->exceptionMapper->mapToHttpException($exception);
        
        // 構造化ログ出力
        Log::channel('domain_errors')->warning('Domain exception occurred', [
            'exception_class' => $exception::class,
            'message' => $exception->getMessage(),
            'error_code' => $mapping['code'],
            'trace_id' => $mapping['trace_id'],
            'stack_trace' => $exception->getTraceAsString(),
        ]);

        $errorResponse = new ErrorResponseDTO(
            success: false,
            message: $mapping['message'],
            errorCode: $mapping['code'],
            traceId: $mapping['trace_id']
        );

        return response()->json($errorResponse->toArray(), $mapping['status']);
    }

    protected function handleValidationException(ValidationException $exception): JsonResponse
    {
        $traceId = uniqid('trace_', true);
        
        Log::channel('validation_errors')->info('Validation error occurred', [
            'errors' => $exception->errors(),
            'trace_id' => $traceId,
        ]);

        $errorResponse = new ErrorResponseDTO(
            success: false,
            message: 'バリデーションエラーが発生しました',
            errorCode: 'VALIDATION_ERROR',
            traceId: $traceId,
            details: $exception->errors()
        );

        return response()->json($errorResponse->toArray(), 422);
    }
}
```

### 3. 構造化ログ設定

#### 3.1 ログチャンネル設定
- `config/logging.php`更新
```php
'channels' => [
    // 既存設定...
    
    'domain_errors' => [
        'driver' => 'daily',
        'path' => storage_path('logs/domain-errors.log'),
        'level' => 'warning',
        'days' => 30,
        'formatter' => Monolog\Formatter\JsonFormatter::class,
    ],
    
    'validation_errors' => [
        'driver' => 'daily',
        'path' => storage_path('logs/validation-errors.log'),
        'level' => 'info',
        'days' => 30,
        'formatter' => Monolog\Formatter\JsonFormatter::class,
    ],
    
    'business_events' => [
        'driver' => 'daily',
        'path' => storage_path('logs/business-events.log'),
        'level' => 'info',
        'days' => 90,
        'formatter' => Monolog\Formatter\JsonFormatter::class,
    ],
],
```

### 4. グローバル例外ハンドラー更新

#### 4.1 Handler.php更新
- `app/Exceptions/Handler.php`
```php
class Handler extends ExceptionHandler
{
    public function render($request, Throwable $exception): Response
    {
        // API リクエストの場合
        if ($request->is('api/*')) {
            return $this->handleApiException($request, $exception);
        }

        return parent::render($request, $exception);
    }

    private function handleApiException($request, Throwable $exception): JsonResponse
    {
        $traceId = uniqid('trace_', true);

        // 予期しない例外のログ出力
        if (!$exception instanceof DomainException && !$exception instanceof ValidationException) {
            Log::channel('stack')->error('Unexpected exception occurred', [
                'exception_class' => $exception::class,
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace_id' => $traceId,
                'request_url' => $request->fullUrl(),
                'request_method' => $request->method(),
                'user_id' => auth()->id(),
            ]);
        }

        $errorResponse = new ErrorResponseDTO(
            success: false,
            message: app()->environment('production') ? 
                'システムエラーが発生しました' : 
                $exception->getMessage(),
            errorCode: 'INTERNAL_SERVER_ERROR',
            traceId: $traceId
        );

        return response()->json($errorResponse->toArray(), 500);
    }
}
```

### 5. ビジネスイベントログ

#### 5.1 BusinessEventLogger作成
- `UseCase/Shared/Logger/BusinessEventLogger.php`
```php
class BusinessEventLogger
{
    public function logGroupCreated(string $groupId, string $groupName): void
    {
        Log::channel('business_events')->info('Group created', [
            'event_type' => 'group_created',
            'group_id' => $groupId,
            'group_name' => $groupName,
            'timestamp' => now()->toISOString(),
        ]);
    }

    public function logChildCreated(string $childId, string $childName, string $groupId): void
    {
        Log::channel('business_events')->info('Child created', [
            'event_type' => 'child_created',
            'child_id' => $childId,
            'child_name' => $childName,
            'group_id' => $groupId,
            'timestamp' => now()->toISOString(),
        ]);
    }

    public function logStockChanged(string $childId, string $categoryId, int $previousCount, int $newCount, string $operation): void
    {
        Log::channel('business_events')->info('Stock changed', [
            'event_type' => 'stock_changed',
            'child_id' => $childId,
            'category_id' => $categoryId,
            'previous_count' => $previousCount,
            'new_count' => $newCount,
            'operation' => $operation, // 'increment' or 'decrement'
            'change_amount' => abs($newCount - $previousCount),
            'timestamp' => now()->toISOString(),
        ]);
    }
}
```

### 6. UseCase更新（ログ追加）

#### 6.1 CreateGroupUseCase更新
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

### 7. エラーレスポンステスト

#### 7.1 ExceptionMappingTest作成
- `tests/Unit/Exception/ExceptionMappingTest.php`
```php
class ExceptionMappingTest extends TestCase
{
    private UnifiedExceptionMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapper = new UnifiedExceptionMapper();
    }

    public function test_group_not_found_exception_mapping()
    {
        $exception = new GroupNotFoundException('Group not found');
        $mapping = $this->mapper->mapToHttpException($exception);

        $this->assertEquals(404, $mapping['status']);
        $this->assertEquals('GROUP_NOT_FOUND', $mapping['code']);
        $this->assertEquals('Group not found', $mapping['message']);
    }

    public function test_business_rule_violation_exception_mapping()
    {
        $exception = new BusinessRuleViolationException('Business rule violated');
        $mapping = $this->mapper->mapToHttpException($exception);

        $this->assertEquals(409, $mapping['status']);
        $this->assertEquals('BUSINESS_RULE_VIOLATION', $mapping['code']);
    }
}
```

### 8. ログテスト

#### 8.1 LoggingTest作成
- `tests/Unit/Logging/LoggingTest.php`
```php
class LoggingTest extends TestCase
{
    public function test_business_event_logging()
    {
        Log::channel('business_events')->info('Test event', [
            'event_type' => 'test_event',
            'test_data' => 'test_value',
        ]);

        // ログファイルの存在確認
        $logPath = storage_path('logs/business-events-' . now()->format('Y-m-d') . '.log');
        $this->assertFileExists($logPath);
        
        // ログ内容の確認
        $logContent = file_get_contents($logPath);
        $this->assertStringContainsString('test_event', $logContent);
    }
}
```

## 実装ルール

### 8.1 例外マッピング原則
- ドメイン例外は適切なHTTPステータスにマッピング
- エラーコードは大文字スネークケースで統一
- メッセージはユーザーフレンドリーに

### 8.2 ログ出力原則
- 構造化ログでJSON形式出力
- 必要な情報をすべて含める（trace_id, timestamp等）
- 個人情報の漏洩防止

### 8.3 レスポンス形式統一
- 成功・失敗レスポンスの形式統一
- trace_idによる追跡可能性確保

## 受け入れ条件
- [ ] 統一例外マッピング体系が構築されている
- [ ] エラーレスポンス形式が標準化されている
- [ ] 構造化ログが適切に出力されている
- [ ] ビジネスイベントログが実装されている
- [ ] グローバル例外ハンドラーが更新されている
- [ ] エラーハンドリングのテストが作成されている
- [ ] ログ出力のテストが作成されている
- [ ] trace_idによる追跡が可能になっている

## 参考
- 統一的なエラーハンドリングで運用性向上
- 構造化ログで問題の迅速な特定
- ユーザーフレンドリーなエラーメッセージ