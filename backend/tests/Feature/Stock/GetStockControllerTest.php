<?php

namespace Tests\Feature\Stock;

use App\Models\Children;
use App\Models\ClothingCategory;
use App\Models\StockItem;
use App\Models\UserGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetStockControllerTest extends TestCase
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

    public function test_can_get_stock_for_existing_child_without_stock_items(): void
    {
        $group = UserGroup::factory()->create();
        $child = Children::factory()->create([
            'user_group_id' => $group->id,
            'name' => '太郎'
        ]);

        $response = $this->getJson("/api/children/{$child->id}/stock");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'ストック情報を取得しました',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'child_id',
                    'child_name',
                    'stock_items' => [
                        '*' => [
                            'clothing_category_id',
                            'clothing_category' => [
                                'id',
                                'name',
                                'icon_path',
                                'sort_order',
                            ],
                            'current_count',
                            'stock_item_id',
                        ]
                    ]
                ]
            ]);

        $responseData = $response->json('data');
        $this->assertEquals($child->id, $responseData['child_id']);
        $this->assertEquals('太郎', $responseData['child_name']);
        $this->assertCount(5, $responseData['stock_items']);

        // All stock items should have count 0 and stock_item_id null
        foreach ($responseData['stock_items'] as $stockItem) {
            $this->assertEquals(0, $stockItem['current_count']);
            $this->assertNull($stockItem['stock_item_id']);
        }
    }

    public function test_can_get_stock_for_existing_child_with_stock_items(): void
    {
        $group = UserGroup::factory()->create();
        $child = Children::factory()->create([
            'user_group_id' => $group->id,
            'name' => '太郎'
        ]);

        $category1 = ClothingCategory::first();
        $category2 = ClothingCategory::skip(1)->first();

        // Create some stock items
        $stockItem1 = StockItem::create([
            'child_id' => $child->id,
            'clothing_category_id' => $category1->id,
            'current_count' => 3
        ]);

        $stockItem2 = StockItem::create([
            'child_id' => $child->id,
            'clothing_category_id' => $category2->id,
            'current_count' => 1
        ]);

        $response = $this->getJson("/api/children/{$child->id}/stock");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'ストック情報を取得しました',
            ]);

        $responseData = $response->json('data');
        $this->assertEquals($child->id, $responseData['child_id']);
        $this->assertEquals('太郎', $responseData['child_name']);
        $this->assertCount(5, $responseData['stock_items']);

        // Find the stock items with counts
        $stockWithCount3 = collect($responseData['stock_items'])
            ->firstWhere('clothing_category_id', $category1->id);
        $stockWithCount1 = collect($responseData['stock_items'])
            ->firstWhere('clothing_category_id', $category2->id);

        $this->assertEquals(3, $stockWithCount3['current_count']);
        $this->assertEquals($stockItem1->id, $stockWithCount3['stock_item_id']);

        $this->assertEquals(1, $stockWithCount1['current_count']);
        $this->assertEquals($stockItem2->id, $stockWithCount1['stock_item_id']);
    }

    public function test_returns_404_for_non_existent_child(): void
    {
        $nonExistentChildId = 99999;

        $response = $this->getJson("/api/children/{$nonExistentChildId}/stock");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => '指定された子どもが見つかりません',
                'data' => null
            ]);
    }

    public function test_stock_items_are_ordered_by_clothing_category_sort_order(): void
    {
        $group = UserGroup::factory()->create();
        $child = Children::factory()->create([
            'user_group_id' => $group->id,
            'name' => '太郎'
        ]);

        $response = $this->getJson("/api/children/{$child->id}/stock");

        $response->assertStatus(200);

        $responseData = $response->json('data');
        $stockItems = $responseData['stock_items'];

        // Verify items are ordered by sort_order
        for ($i = 0; $i < count($stockItems) - 1; $i++) {
            $currentSortOrder = $stockItems[$i]['clothing_category']['sort_order'];
            $nextSortOrder = $stockItems[$i + 1]['clothing_category']['sort_order'];
            $this->assertLessThanOrEqual($nextSortOrder, $currentSortOrder);
        }
    }

    public function test_response_structure_is_correct(): void
    {
        $group = UserGroup::factory()->create();
        $child = Children::factory()->create([
            'user_group_id' => $group->id,
            'name' => '太郎'
        ]);

        $response = $this->getJson("/api/children/{$child->id}/stock");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'child_id',
                    'child_name',
                    'stock_items' => [
                        '*' => [
                            'clothing_category_id',
                            'clothing_category' => [
                                'id',
                                'name',
                                'icon_path',
                                'sort_order',
                            ],
                            'current_count',
                            'stock_item_id',
                        ]
                    ]
                ]
            ]);

        $responseData = $response->json();
        $this->assertTrue($responseData['success']);
        $this->assertIsInt($responseData['data']['child_id']);
        $this->assertIsString($responseData['data']['child_name']);
        $this->assertIsArray($responseData['data']['stock_items']);
    }

    public function test_handles_invalid_child_id_gracefully(): void
    {
        $response = $this->getJson("/api/children/invalid/stock");

        // Should return 500 due to type error (int expected, string given)
        $response->assertStatus(500);
    }
}