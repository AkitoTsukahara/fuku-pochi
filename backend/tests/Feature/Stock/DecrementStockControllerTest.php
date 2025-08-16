<?php

namespace Tests\Feature\Stock;

use App\Models\Children;
use App\Models\ClothingCategory;
use App\Models\StockItem;
use App\Models\UserGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DecrementStockControllerTest extends TestCase
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

    public function test_can_decrement_stock_for_existing_stock_item(): void
    {
        $group = UserGroup::factory()->create();
        $child = Children::factory()->create([
            'user_group_id' => $group->id,
            'name' => '太郎'
        ]);
        $category = ClothingCategory::first();

        // Create existing stock item with count 5
        $stockItem = StockItem::create([
            'child_id' => $child->id,
            'clothing_category_id' => $category->id,
            'current_count' => 5
        ]);

        $response = $this->postJson("/api/children/{$child->id}/stock-decrement", [
            'clothing_category_id' => $category->id,
            'decrement' => 2
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'ストック数を減少しました',
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
        $this->assertEquals(3, $responseData['stock_item']['current_count']); // 5 - 2 = 3
        $this->assertEquals($stockItem->id, $responseData['stock_item']['id']);

        // Verify database
        $this->assertDatabaseHas('stock_items', [
            'id' => $stockItem->id,
            'child_id' => $child->id,
            'clothing_category_id' => $category->id,
            'current_count' => 3
        ]);
    }

    public function test_can_decrement_stock_to_zero(): void
    {
        $group = UserGroup::factory()->create();
        $child = Children::factory()->create(['user_group_id' => $group->id]);
        $category = ClothingCategory::first();

        // Create existing stock item with count 3
        $stockItem = StockItem::create([
            'child_id' => $child->id,
            'clothing_category_id' => $category->id,
            'current_count' => 3
        ]);

        $response = $this->postJson("/api/children/{$child->id}/stock-decrement", [
            'clothing_category_id' => $category->id,
            'decrement' => 3
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'ストック数を減少しました',
            ]);

        $responseData = $response->json('data');
        $this->assertEquals(0, $responseData['stock_item']['current_count']);

        // Verify database
        $this->assertDatabaseHas('stock_items', [
            'id' => $stockItem->id,
            'current_count' => 0
        ]);
    }

    public function test_cannot_decrement_stock_to_negative(): void
    {
        $group = UserGroup::factory()->create();
        $child = Children::factory()->create(['user_group_id' => $group->id]);
        $category = ClothingCategory::first();

        // Create existing stock item with count 2
        $stockItem = StockItem::create([
            'child_id' => $child->id,
            'clothing_category_id' => $category->id,
            'current_count' => 2
        ]);

        $response = $this->postJson("/api/children/{$child->id}/stock-decrement", [
            'clothing_category_id' => $category->id,
            'decrement' => 3
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'ストック数が0未満になるため減少できません',
                'data' => [
                    'current_count' => 2,
                    'requested_decrement' => 3
                ]
            ]);

        // Verify database remains unchanged
        $this->assertDatabaseHas('stock_items', [
            'id' => $stockItem->id,
            'current_count' => 2
        ]);
    }

    public function test_returns_404_for_non_existent_stock_item(): void
    {
        $group = UserGroup::factory()->create();
        $child = Children::factory()->create(['user_group_id' => $group->id]);
        $category = ClothingCategory::first();

        $response = $this->postJson("/api/children/{$child->id}/stock-decrement", [
            'clothing_category_id' => $category->id,
            'decrement' => 1
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => '指定されたアイテムのストックが存在しません',
                'data' => null
            ]);

        $this->assertDatabaseCount('stock_items', 0);
    }

    public function test_returns_404_for_non_existent_child(): void
    {
        $category = ClothingCategory::first();
        $nonExistentChildId = 99999;

        $response = $this->postJson("/api/children/{$nonExistentChildId}/stock-decrement", [
            'clothing_category_id' => $category->id,
            'decrement' => 1
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => '指定された子どもが見つかりません',
                'data' => null
            ]);
    }

    public function test_cannot_decrement_stock_without_clothing_category_id(): void
    {
        $group = UserGroup::factory()->create();
        $child = Children::factory()->create(['user_group_id' => $group->id]);

        $response = $this->postJson("/api/children/{$child->id}/stock-decrement", [
            'decrement' => 1
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['clothing_category_id'])
            ->assertJson([
                'message' => '衣類カテゴリIDは必須です',
                'errors' => [
                    'clothing_category_id' => ['衣類カテゴリIDは必須です']
                ]
            ]);
    }

    public function test_cannot_decrement_stock_without_decrement(): void
    {
        $group = UserGroup::factory()->create();
        $child = Children::factory()->create(['user_group_id' => $group->id]);
        $category = ClothingCategory::first();

        $response = $this->postJson("/api/children/{$child->id}/stock-decrement", [
            'clothing_category_id' => $category->id
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['decrement'])
            ->assertJson([
                'message' => '減少数は必須です',
                'errors' => [
                    'decrement' => ['減少数は必須です']
                ]
            ]);
    }

    public function test_cannot_decrement_stock_with_zero_decrement(): void
    {
        $group = UserGroup::factory()->create();
        $child = Children::factory()->create(['user_group_id' => $group->id]);
        $category = ClothingCategory::first();

        $response = $this->postJson("/api/children/{$child->id}/stock-decrement", [
            'clothing_category_id' => $category->id,
            'decrement' => 0
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['decrement'])
            ->assertJson([
                'message' => '減少数は1以上で入力してください',
                'errors' => [
                    'decrement' => ['減少数は1以上で入力してください']
                ]
            ]);
    }

    public function test_cannot_decrement_stock_with_negative_decrement(): void
    {
        $group = UserGroup::factory()->create();
        $child = Children::factory()->create(['user_group_id' => $group->id]);
        $category = ClothingCategory::first();

        $response = $this->postJson("/api/children/{$child->id}/stock-decrement", [
            'clothing_category_id' => $category->id,
            'decrement' => -1
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['decrement'])
            ->assertJson([
                'message' => '減少数は1以上で入力してください',
                'errors' => [
                    'decrement' => ['減少数は1以上で入力してください']
                ]
            ]);
    }

    public function test_cannot_decrement_stock_with_non_integer_decrement(): void
    {
        $group = UserGroup::factory()->create();
        $child = Children::factory()->create(['user_group_id' => $group->id]);
        $category = ClothingCategory::first();

        $response = $this->postJson("/api/children/{$child->id}/stock-decrement", [
            'clothing_category_id' => $category->id,
            'decrement' => 'abc'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['decrement'])
            ->assertJson([
                'message' => '減少数は整数で入力してください',
                'errors' => [
                    'decrement' => ['減少数は整数で入力してください']
                ]
            ]);
    }

    public function test_cannot_decrement_stock_with_non_existent_clothing_category(): void
    {
        $group = UserGroup::factory()->create();
        $child = Children::factory()->create(['user_group_id' => $group->id]);

        $response = $this->postJson("/api/children/{$child->id}/stock-decrement", [
            'clothing_category_id' => 99999,
            'decrement' => 1
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['clothing_category_id'])
            ->assertJson([
                'message' => '指定された衣類カテゴリが存在しません',
                'errors' => [
                    'clothing_category_id' => ['指定された衣類カテゴリが存在しません']
                ]
            ]);
    }

    public function test_response_structure_is_correct(): void
    {
        $group = UserGroup::factory()->create();
        $child = Children::factory()->create(['user_group_id' => $group->id]);
        $category = ClothingCategory::first();

        // Create existing stock item
        StockItem::create([
            'child_id' => $child->id,
            'clothing_category_id' => $category->id,
            'current_count' => 5
        ]);

        $response = $this->postJson("/api/children/{$child->id}/stock-decrement", [
            'clothing_category_id' => $category->id,
            'decrement' => 2
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

    public function test_different_children_can_have_different_stock_counts(): void
    {
        $group = UserGroup::factory()->create();
        $child1 = Children::factory()->create(['user_group_id' => $group->id]);
        $child2 = Children::factory()->create(['user_group_id' => $group->id]);
        $category = ClothingCategory::first();

        // Create stock items for both children
        $stockItem1 = StockItem::create([
            'child_id' => $child1->id,
            'clothing_category_id' => $category->id,
            'current_count' => 5
        ]);

        $stockItem2 = StockItem::create([
            'child_id' => $child2->id,
            'clothing_category_id' => $category->id,
            'current_count' => 3
        ]);

        // Decrement for child1
        $response1 = $this->postJson("/api/children/{$child1->id}/stock-decrement", [
            'clothing_category_id' => $category->id,
            'decrement' => 2
        ]);

        $response1->assertStatus(200);

        // Verify only child1's stock was affected
        $this->assertDatabaseHas('stock_items', [
            'id' => $stockItem1->id,
            'current_count' => 3 // 5 - 2 = 3
        ]);

        $this->assertDatabaseHas('stock_items', [
            'id' => $stockItem2->id,
            'current_count' => 3 // unchanged
        ]);
    }
}