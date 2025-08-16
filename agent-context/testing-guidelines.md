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

### 今後の方針
1. **新規API実装時**: 必ずFeatureテストも同時実装
2. **テストファースト**: 可能な限りTDD（テスト駆動開発）を採用
3. **継続的改善**: テストケースの充実と品質向上
4. **回帰テスト**: 既存機能の動作保証