<?php

namespace Tests\Feature\Stock;

use App\Models\Children;
use App\Models\ClothingCategory;
use App\Models\StockItem;
use App\Models\UserGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncrementStockControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed clothing categories for tests
        $this->seedClothingCategories();
    }

    private function seedClothingCategories(): void
    {
        $categories = [
            ['name' => 'Tシャツ', 'icon_path' => '/icons/tshirt.svg', 'sort_order' => 1],
            ['name' => 'ズボン', 'icon_path' => '/icons/pants.svg', 'sort_order' => 2],
            ['name' => '靴下', 'icon_path' => '/icons/socks.svg', 'sort_order' => 3],
            ['name' => 'ハンカチ', 'icon_path' => '/icons/handkerchief.svg', 'sort_order' => 4],
            ['name' => '肌着', 'icon_path' => '/icons/underwear.svg', 'sort_order' => 5],
        ];

        foreach ($categories as $category) {
            ClothingCategory::create($category);
        }
    }

    public function test_can_increment_stock_for_new_stock_item(): void
    {
        $group = UserGroup::factory()->create();
        $child = Children::factory()->create([
            'user_group_id' => $group->id,
            'name' => '太郎'
        ]);
        $category = ClothingCategory::first();

        $response = $this->postJson("/api/children/{$child->id}/stock-increment", [
            'clothing_category_id' => $category->id,
            'increment' => 3
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'ストック数を増加しました',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'child_id',
                    'child_name',
                    'stock_item' => [
                        'id',
                        'clothing_category_id',
                        'clothing_category' => [
                            'id',
                            'name',
                            'icon_path',
                            'sort_order',
                        ],
                        'current_count',
                    ]
                ]
            ]);

        $responseData = $response->json('data');
        $this->assertEquals($child->id, $responseData['child_id']);
        $this->assertEquals('太郎', $responseData['child_name']);
        $this->assertEquals(3, $responseData['stock_item']['current_count']);
        $this->assertEquals($category->id, $responseData['stock_item']['clothing_category_id']);

        // Verify database
        $this->assertDatabaseHas('stock_items', [
            'child_id' => $child->id,
            'clothing_category_id' => $category->id,
            'current_count' => 3
        ]);
    }

    public function test_can_increment_stock_for_existing_stock_item(): void
    {
        $group = UserGroup::factory()->create();
        $child = Children::factory()->create([
            'user_group_id' => $group->id,
            'name' => '太郎'
        ]);
        $category = ClothingCategory::first();

        // Create existing stock item
        $stockItem = StockItem::create([
            'child_id' => $child->id,
            'clothing_category_id' => $category->id,
            'current_count' => 2
        ]);

        $response = $this->postJson("/api/children/{$child->id}/stock-increment", [
            'clothing_category_id' => $category->id,
            'increment' => 3
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'ストック数を増加しました',
            ]);

        $responseData = $response->json('data');
        $this->assertEquals(5, $responseData['stock_item']['current_count']); // 2 + 3 = 5
        $this->assertEquals($stockItem->id, $responseData['stock_item']['id']);

        // Verify database
        $this->assertDatabaseHas('stock_items', [
            'id' => $stockItem->id,
            'child_id' => $child->id,
            'clothing_category_id' => $category->id,
            'current_count' => 5
        ]);
    }

    public function test_cannot_increment_stock_without_clothing_category_id(): void
    {
        $group = UserGroup::factory()->create();
        $child = Children::factory()->create(['user_group_id' => $group->id]);

        $response = $this->postJson("/api/children/{$child->id}/stock-increment", [
            'increment' => 3
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['clothing_category_id'])
            ->assertJson([
                'message' => '衣類カテゴリIDは必須です',
                'errors' => [
                    'clothing_category_id' => ['衣類カテゴリIDは必須です']
                ]
            ]);

        $this->assertDatabaseCount('stock_items', 0);
    }

    public function test_cannot_increment_stock_without_increment(): void
    {
        $group = UserGroup::factory()->create();
        $child = Children::factory()->create(['user_group_id' => $group->id]);
        $category = ClothingCategory::first();

        $response = $this->postJson("/api/children/{$child->id}/stock-increment", [
            'clothing_category_id' => $category->id
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['increment'])
            ->assertJson([
                'message' => '増加数は必須です',
                'errors' => [
                    'increment' => ['増加数は必須です']
                ]
            ]);

        $this->assertDatabaseCount('stock_items', 0);
    }

    public function test_cannot_increment_stock_with_zero_increment(): void
    {
        $group = UserGroup::factory()->create();
        $child = Children::factory()->create(['user_group_id' => $group->id]);
        $category = ClothingCategory::first();

        $response = $this->postJson("/api/children/{$child->id}/stock-increment", [
            'clothing_category_id' => $category->id,
            'increment' => 0
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['increment'])
            ->assertJson([
                'message' => '増加数は1以上で入力してください',
                'errors' => [
                    'increment' => ['増加数は1以上で入力してください']
                ]
            ]);

        $this->assertDatabaseCount('stock_items', 0);
    }

    public function test_cannot_increment_stock_with_negative_increment(): void
    {
        $group = UserGroup::factory()->create();
        $child = Children::factory()->create(['user_group_id' => $group->id]);
        $category = ClothingCategory::first();

        $response = $this->postJson("/api/children/{$child->id}/stock-increment", [
            'clothing_category_id' => $category->id,
            'increment' => -1
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['increment'])
            ->assertJson([
                'message' => '増加数は1以上で入力してください',
                'errors' => [
                    'increment' => ['増加数は1以上で入力してください']
                ]
            ]);

        $this->assertDatabaseCount('stock_items', 0);
    }

    public function test_cannot_increment_stock_with_non_integer_increment(): void
    {
        $group = UserGroup::factory()->create();
        $child = Children::factory()->create(['user_group_id' => $group->id]);
        $category = ClothingCategory::first();

        $response = $this->postJson("/api/children/{$child->id}/stock-increment", [
            'clothing_category_id' => $category->id,
            'increment' => 'abc'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['increment'])
            ->assertJson([
                'message' => '増加数は整数で入力してください',
                'errors' => [
                    'increment' => ['増加数は整数で入力してください']
                ]
            ]);

        $this->assertDatabaseCount('stock_items', 0);
    }

    public function test_cannot_increment_stock_with_non_existent_clothing_category(): void
    {
        $group = UserGroup::factory()->create();
        $child = Children::factory()->create(['user_group_id' => $group->id]);

        $response = $this->postJson("/api/children/{$child->id}/stock-increment", [
            'clothing_category_id' => 99999,
            'increment' => 3
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['clothing_category_id'])
            ->assertJson([
                'message' => '指定された衣類カテゴリが存在しません',
                'errors' => [
                    'clothing_category_id' => ['指定された衣類カテゴリが存在しません']
                ]
            ]);

        $this->assertDatabaseCount('stock_items', 0);
    }

    public function test_returns_404_for_non_existent_child(): void
    {
        $category = ClothingCategory::first();
        $nonExistentChildId = 99999;

        $response = $this->postJson("/api/children/{$nonExistentChildId}/stock-increment", [
            'clothing_category_id' => $category->id,
            'increment' => 3
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => '指定された子どもが見つかりません',
                'data' => null
            ]);

        $this->assertDatabaseCount('stock_items', 0);
    }

    public function test_can_increment_stock_with_large_number(): void
    {
        $group = UserGroup::factory()->create();
        $child = Children::factory()->create(['user_group_id' => $group->id]);
        $category = ClothingCategory::first();

        $response = $this->postJson("/api/children/{$child->id}/stock-increment", [
            'clothing_category_id' => $category->id,
            'increment' => 100
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'ストック数を増加しました',
            ]);

        $responseData = $response->json('data');
        $this->assertEquals(100, $responseData['stock_item']['current_count']);

        $this->assertDatabaseHas('stock_items', [
            'child_id' => $child->id,
            'clothing_category_id' => $category->id,
            'current_count' => 100
        ]);
    }

    public function test_response_structure_is_correct(): void
    {
        $group = UserGroup::factory()->create();
        $child = Children::factory()->create(['user_group_id' => $group->id]);
        $category = ClothingCategory::first();

        $response = $this->postJson("/api/children/{$child->id}/stock-increment", [
            'clothing_category_id' => $category->id,
            'increment' => 3
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'child_id',
                    'child_name',
                    'stock_item' => [
                        'id',
                        'clothing_category_id',
                        'clothing_category' => [
                            'id',
                            'name',
                            'icon_path',
                            'sort_order',
                        ],
                        'current_count',
                    ]
                ]
            ]);

        $responseData = $response->json();
        $this->assertTrue($responseData['success']);
        $this->assertIsInt($responseData['data']['child_id']);
        $this->assertIsString($responseData['data']['child_name']);
        $this->assertIsInt($responseData['data']['stock_item']['id']);
        $this->assertIsInt($responseData['data']['stock_item']['current_count']);
    }

    public function test_cannot_create_duplicate_stock_items(): void
    {
        $group = UserGroup::factory()->create();
        $child = Children::factory()->create(['user_group_id' => $group->id]);
        $category = ClothingCategory::first();

        // Create first stock item
        $response1 = $this->postJson("/api/children/{$child->id}/stock-increment", [
            'clothing_category_id' => $category->id,
            'increment' => 2
        ]);

        // Create second stock item for same child and category (should update existing)
        $response2 = $this->postJson("/api/children/{$child->id}/stock-increment", [
            'clothing_category_id' => $category->id,
            'increment' => 3
        ]);

        $response1->assertStatus(200);
        $response2->assertStatus(200);

        // Should only have one stock item in database
        $this->assertDatabaseCount('stock_items', 1);
        
        // Should have total count of 5 (2 + 3)
        $this->assertDatabaseHas('stock_items', [
            'child_id' => $child->id,
            'clothing_category_id' => $category->id,
            'current_count' => 5
        ]);

        $responseData2 = $response2->json('data');
        $this->assertEquals(5, $responseData2['stock_item']['current_count']);
    }
}