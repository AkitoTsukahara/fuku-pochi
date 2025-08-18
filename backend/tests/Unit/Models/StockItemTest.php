<?php

namespace Tests\Unit\Models;

use App\Models\Children;
use App\Models\ClothingCategory;
use App\Models\StockItem;
use App\Models\UserGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_fillable_attributes(): void
    {
        $stockItem = new StockItem();
        $expected = [
            'child_id',
            'clothing_category_id',
            'current_count',
        ];

        $this->assertEquals($expected, $stockItem->getFillable());
    }

    public function test_has_correct_casts(): void
    {
        $stockItem = new StockItem();
        $expected = [
            'current_count' => 'integer',
            'id' => 'int',
        ];

        $this->assertEquals($expected, $stockItem->getCasts());
    }

    public function test_current_count_is_cast_to_integer(): void
    {
        $clothingCategory = ClothingCategory::create([
            'name' => 'Tシャツ',
            'icon_path' => '/icons/tshirt.svg',
            'sort_order' => 1
        ]);
        
        $stockItem = StockItem::factory()->create([
            'clothing_category_id' => $clothingCategory->id,
            'current_count' => '5'
        ]);

        $this->assertIsInt($stockItem->current_count);
        $this->assertEquals(5, $stockItem->current_count);
    }

    public function test_belongs_to_child_relationship(): void
    {
        $child = Children::factory()->create();
        $clothingCategory = ClothingCategory::create([
            'name' => 'ズボン',
            'icon_path' => '/icons/pants.svg',
            'sort_order' => 2
        ]);
        
        $stockItem = StockItem::factory()->create([
            'child_id' => $child->id,
            'clothing_category_id' => $clothingCategory->id
        ]);

        $this->assertInstanceOf(Children::class, $stockItem->child);
        $this->assertEquals($child->id, $stockItem->child->id);
    }

    public function test_belongs_to_clothing_category_relationship(): void
    {
        $clothingCategory = ClothingCategory::create([
            'name' => 'Tシャツ',
            'icon_path' => '/icons/tshirt.svg',
            'sort_order' => 1
        ]);
        
        $stockItem = StockItem::factory()->create([
            'clothing_category_id' => $clothingCategory->id
        ]);

        $this->assertInstanceOf(ClothingCategory::class, $stockItem->clothingCategory);
        $this->assertEquals($clothingCategory->id, $stockItem->clothingCategory->id);
        $this->assertEquals('Tシャツ', $stockItem->clothingCategory->name);
    }

    public function test_can_create_stock_item_with_required_attributes(): void
    {
        $child = Children::factory()->create();
        $clothingCategory = ClothingCategory::create([
            'name' => 'ズボン',
            'icon_path' => '/icons/pants.svg',
            'sort_order' => 2
        ]);

        $stockItem = StockItem::create([
            'child_id' => $child->id,
            'clothing_category_id' => $clothingCategory->id,
            'current_count' => 3
        ]);

        $this->assertEquals($child->id, $stockItem->child_id);
        $this->assertEquals($clothingCategory->id, $stockItem->clothing_category_id);
        $this->assertEquals(3, $stockItem->current_count);
        
        $this->assertDatabaseHas('stock_items', [
            'child_id' => $child->id,
            'clothing_category_id' => $clothingCategory->id,
            'current_count' => 3
        ]);
    }

    public function test_model_uses_timestamps(): void
    {
        $clothingCategory = ClothingCategory::create([
            'name' => '靴下',
            'icon_path' => '/icons/socks.svg',
            'sort_order' => 3
        ]);
        
        $stockItem = StockItem::factory()->create([
            'clothing_category_id' => $clothingCategory->id
        ]);

        $this->assertNotNull($stockItem->created_at);
        $this->assertNotNull($stockItem->updated_at);
    }

    public function test_can_have_zero_count(): void
    {
        $clothingCategory = ClothingCategory::create([
            'name' => 'ハンカチ',
            'icon_path' => '/icons/handkerchief.svg',
            'sort_order' => 4
        ]);
        
        $stockItem = StockItem::factory()->create([
            'clothing_category_id' => $clothingCategory->id,
            'current_count' => 0
        ]);

        $this->assertEquals(0, $stockItem->current_count);
        $this->assertIsInt($stockItem->current_count);
    }

    public function test_can_have_large_count(): void
    {
        $clothingCategory = ClothingCategory::create([
            'name' => '肌着',
            'icon_path' => '/icons/underwear.svg',
            'sort_order' => 5
        ]);
        
        $stockItem = StockItem::factory()->create([
            'clothing_category_id' => $clothingCategory->id,
            'current_count' => 999
        ]);

        $this->assertEquals(999, $stockItem->current_count);
        $this->assertIsInt($stockItem->current_count);
    }

    public function test_child_relationship_includes_correct_data(): void
    {
        $userGroup = UserGroup::factory()->create(['name' => 'テストグループ']);
        $child = Children::factory()->create([
            'user_group_id' => $userGroup->id,
            'name' => '太郎'
        ]);
        
        $clothingCategory = ClothingCategory::create([
            'name' => 'テスト',
            'icon_path' => '/icons/test.svg',
            'sort_order' => 6
        ]);
        
        $stockItem = StockItem::factory()->create([
            'child_id' => $child->id,
            'clothing_category_id' => $clothingCategory->id
        ]);

        $relatedChild = $stockItem->child;
        
        $this->assertEquals('太郎', $relatedChild->name);
        $this->assertEquals($userGroup->id, $relatedChild->user_group_id);
    }

    public function test_clothing_category_relationship_includes_correct_data(): void
    {
        $clothingCategory = ClothingCategory::create([
            'name' => '靴下',
            'icon_path' => '/icons/socks.svg',
            'sort_order' => 3
        ]);
        
        $stockItem = StockItem::factory()->create([
            'clothing_category_id' => $clothingCategory->id
        ]);

        $relatedCategory = $stockItem->clothingCategory;
        
        $this->assertEquals('靴下', $relatedCategory->name);
        $this->assertEquals('/icons/socks.svg', $relatedCategory->icon_path);
        $this->assertEquals(3, $relatedCategory->sort_order);
    }

    public function test_multiple_stock_items_can_belong_to_same_child(): void
    {
        $child = Children::factory()->create();
        $category1 = ClothingCategory::create(['name' => 'Tシャツ', 'icon_path' => '/icons/tshirt.svg', 'sort_order' => 1]);
        $category2 = ClothingCategory::create(['name' => 'ズボン', 'icon_path' => '/icons/pants.svg', 'sort_order' => 2]);

        $stockItem1 = StockItem::factory()->create([
            'child_id' => $child->id,
            'clothing_category_id' => $category1->id,
            'current_count' => 2
        ]);

        $stockItem2 = StockItem::factory()->create([
            'child_id' => $child->id,
            'clothing_category_id' => $category2->id,
            'current_count' => 3
        ]);

        $this->assertEquals($child->id, $stockItem1->child_id);
        $this->assertEquals($child->id, $stockItem2->child_id);
        $this->assertNotEquals($stockItem1->id, $stockItem2->id);
        
        $childStockItems = $child->stockItems;
        $this->assertCount(2, $childStockItems);
    }
}