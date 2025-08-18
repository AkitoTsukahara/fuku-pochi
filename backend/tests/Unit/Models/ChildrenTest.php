<?php

namespace Tests\Unit\Models;

use App\Models\Children;
use App\Models\ClothingCategory;
use App\Models\StockItem;
use App\Models\UserGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChildrenTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_fillable_attributes(): void
    {
        $children = new Children();
        $expected = [
            'user_group_id',
            'name',
        ];

        $this->assertEquals($expected, $children->getFillable());
    }

    public function test_belongs_to_user_group_relationship(): void
    {
        $userGroup = UserGroup::factory()->create([
            'name' => 'テストグループ'
        ]);
        
        $child = Children::factory()->create([
            'user_group_id' => $userGroup->id,
            'name' => '太郎'
        ]);

        $this->assertInstanceOf(UserGroup::class, $child->userGroup);
        $this->assertEquals($userGroup->id, $child->userGroup->id);
        $this->assertEquals('テストグループ', $child->userGroup->name);
    }

    public function test_has_many_stock_items_relationship(): void
    {
        $child = Children::factory()->create();
        $category1 = ClothingCategory::create([
            'name' => 'Tシャツ',
            'icon_path' => '/icons/tshirt.svg',
            'sort_order' => 1
        ]);
        $category2 = ClothingCategory::create([
            'name' => 'ズボン',
            'icon_path' => '/icons/pants.svg',
            'sort_order' => 2
        ]);
        $category3 = ClothingCategory::create([
            'name' => '靴下',
            'icon_path' => '/icons/socks.svg',
            'sort_order' => 3
        ]);
        
        StockItem::factory()->create([
            'child_id' => $child->id,
            'clothing_category_id' => $category1->id
        ]);
        StockItem::factory()->create([
            'child_id' => $child->id,
            'clothing_category_id' => $category2->id
        ]);
        StockItem::factory()->create([
            'child_id' => $child->id,
            'clothing_category_id' => $category3->id
        ]);

        $this->assertCount(3, $child->stockItems);
        $this->assertInstanceOf(StockItem::class, $child->stockItems->first());
    }

    public function test_stock_items_relationship_returns_empty_collection_when_no_stock_items(): void
    {
        $child = Children::factory()->create();

        $this->assertCount(0, $child->stockItems);
    }

    public function test_can_access_stock_items_through_relationship(): void
    {
        $child = Children::factory()->create();
        $clothingCategory = ClothingCategory::create([
            'name' => 'ズボン',
            'icon_path' => '/icons/pants.svg',
            'sort_order' => 2
        ]);
        
        $stockItem = StockItem::factory()->create([
            'child_id' => $child->id,
            'clothing_category_id' => $clothingCategory->id,
            'current_count' => 5
        ]);

        $retrievedStockItem = $child->stockItems()->first();
        
        $this->assertEquals($stockItem->id, $retrievedStockItem->id);
        $this->assertEquals(5, $retrievedStockItem->current_count);
        $this->assertEquals($child->id, $retrievedStockItem->child_id);
    }

    public function test_model_has_correct_table_name(): void
    {
        $children = new Children();
        
        $this->assertEquals('children', $children->getTable());
    }

    public function test_model_uses_timestamps(): void
    {
        $child = Children::create([
            'user_group_id' => UserGroup::factory()->create()->id,
            'name' => '太郎'
        ]);

        $this->assertNotNull($child->created_at);
        $this->assertNotNull($child->updated_at);
    }

    public function test_can_create_child_with_required_attributes(): void
    {
        $userGroup = UserGroup::factory()->create();
        
        $child = Children::create([
            'user_group_id' => $userGroup->id,
            'name' => '花子'
        ]);

        $this->assertEquals($userGroup->id, $child->user_group_id);
        $this->assertEquals('花子', $child->name);
        $this->assertDatabaseHas('children', [
            'user_group_id' => $userGroup->id,
            'name' => '花子'
        ]);
    }

    public function test_multiple_children_can_belong_to_same_user_group(): void
    {
        $userGroup = UserGroup::factory()->create();
        
        $child1 = Children::factory()->create([
            'user_group_id' => $userGroup->id,
            'name' => '太郎'
        ]);
        
        $child2 = Children::factory()->create([
            'user_group_id' => $userGroup->id,
            'name' => '花子'
        ]);

        $this->assertEquals($userGroup->id, $child1->user_group_id);
        $this->assertEquals($userGroup->id, $child2->user_group_id);
        $this->assertNotEquals($child1->id, $child2->id);
    }

    public function test_children_relationship_with_user_group_is_properly_linked(): void
    {
        $userGroup = UserGroup::factory()->create();
        $child = Children::factory()->create([
            'user_group_id' => $userGroup->id
        ]);

        $userGroupChildren = $userGroup->children;
        
        $this->assertCount(1, $userGroupChildren);
        $this->assertEquals($child->id, $userGroupChildren->first()->id);
    }
}