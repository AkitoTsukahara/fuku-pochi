<?php

namespace Tests\Unit\Models;

use App\Models\ClothingCategory;
use App\Models\StockItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClothingCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_fillable_attributes(): void
    {
        $clothingCategory = new ClothingCategory();
        $expected = [
            'name',
            'icon_path',
            'sort_order',
        ];

        $this->assertEquals($expected, $clothingCategory->getFillable());
    }

    public function test_has_many_stock_items_relationship(): void
    {
        $clothingCategory = ClothingCategory::create([
            'name' => 'Tシャツ',
            'icon_path' => '/icons/tshirt.svg',
            'sort_order' => 1
        ]);

        $stockItems = StockItem::factory(3)->create([
            'clothing_category_id' => $clothingCategory->id
        ]);

        $this->assertCount(3, $clothingCategory->stockItems);
        $this->assertInstanceOf(StockItem::class, $clothingCategory->stockItems->first());
    }

    public function test_stock_items_relationship_returns_empty_collection_when_no_stock_items(): void
    {
        $clothingCategory = ClothingCategory::create([
            'name' => 'ズボン',
            'icon_path' => '/icons/pants.svg',
            'sort_order' => 2
        ]);

        $this->assertCount(0, $clothingCategory->stockItems);
    }

    public function test_can_create_clothing_category_with_required_attributes(): void
    {
        $clothingCategory = ClothingCategory::create([
            'name' => '靴下',
            'icon_path' => '/icons/socks.svg',
            'sort_order' => 3
        ]);

        $this->assertEquals('靴下', $clothingCategory->name);
        $this->assertEquals('/icons/socks.svg', $clothingCategory->icon_path);
        $this->assertEquals(3, $clothingCategory->sort_order);
        
        $this->assertDatabaseHas('clothing_categories', [
            'name' => '靴下',
            'icon_path' => '/icons/socks.svg',
            'sort_order' => 3
        ]);
    }

    public function test_model_uses_timestamps(): void
    {
        $clothingCategory = ClothingCategory::create([
            'name' => 'ハンカチ',
            'icon_path' => '/icons/handkerchief.svg',
            'sort_order' => 4
        ]);

        $this->assertNotNull($clothingCategory->created_at);
        $this->assertNotNull($clothingCategory->updated_at);
    }

    public function test_can_access_stock_items_through_relationship(): void
    {
        $clothingCategory = ClothingCategory::create([
            'name' => '肌着',
            'icon_path' => '/icons/underwear.svg',
            'sort_order' => 5
        ]);

        $stockItem = StockItem::factory()->create([
            'clothing_category_id' => $clothingCategory->id,
            'current_count' => 7
        ]);

        $retrievedStockItem = $clothingCategory->stockItems()->first();
        
        $this->assertEquals($stockItem->id, $retrievedStockItem->id);
        $this->assertEquals(7, $retrievedStockItem->current_count);
        $this->assertEquals($clothingCategory->id, $retrievedStockItem->clothing_category_id);
    }

    public function test_multiple_stock_items_can_use_same_clothing_category(): void
    {
        $clothingCategory = ClothingCategory::create([
            'name' => 'Tシャツ',
            'icon_path' => '/icons/tshirt.svg',
            'sort_order' => 1
        ]);

        $stockItem1 = StockItem::factory()->create([
            'clothing_category_id' => $clothingCategory->id,
            'current_count' => 2
        ]);

        $stockItem2 = StockItem::factory()->create([
            'clothing_category_id' => $clothingCategory->id,
            'current_count' => 3
        ]);

        $categoryStockItems = $clothingCategory->stockItems;
        
        $this->assertCount(2, $categoryStockItems);
        $this->assertEquals($clothingCategory->id, $stockItem1->clothing_category_id);
        $this->assertEquals($clothingCategory->id, $stockItem2->clothing_category_id);
        $this->assertNotEquals($stockItem1->id, $stockItem2->id);
    }

    public function test_can_create_categories_with_different_sort_orders(): void
    {
        $category1 = ClothingCategory::create([
            'name' => 'カテゴリ1',
            'icon_path' => '/icons/category1.svg',
            'sort_order' => 1
        ]);

        $category2 = ClothingCategory::create([
            'name' => 'カテゴリ2',
            'icon_path' => '/icons/category2.svg',
            'sort_order' => 10
        ]);

        $this->assertEquals(1, $category1->sort_order);
        $this->assertEquals(10, $category2->sort_order);
        $this->assertNotEquals($category1->sort_order, $category2->sort_order);
    }

    public function test_sort_order_can_be_zero(): void
    {
        $clothingCategory = ClothingCategory::create([
            'name' => '優先度最高',
            'icon_path' => '/icons/priority.svg',
            'sort_order' => 0
        ]);

        $this->assertEquals(0, $clothingCategory->sort_order);
    }

    public function test_icon_path_can_be_null(): void
    {
        $clothingCategory = ClothingCategory::create([
            'name' => 'アイコンなし',
            'icon_path' => null,
            'sort_order' => 1
        ]);

        $this->assertNull($clothingCategory->icon_path);
        $this->assertEquals('アイコンなし', $clothingCategory->name);
    }

    public function test_clothing_category_relationship_with_stock_items_is_properly_linked(): void
    {
        $clothingCategory = ClothingCategory::create([
            'name' => 'テストカテゴリ',
            'icon_path' => '/icons/test.svg',
            'sort_order' => 1
        ]);

        $stockItem = StockItem::factory()->create([
            'clothing_category_id' => $clothingCategory->id
        ]);

        $stockItemCategory = $stockItem->clothingCategory;
        
        $this->assertEquals($clothingCategory->id, $stockItemCategory->id);
        $this->assertEquals('テストカテゴリ', $stockItemCategory->name);
    }
}