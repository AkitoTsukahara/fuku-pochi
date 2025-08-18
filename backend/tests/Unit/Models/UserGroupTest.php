<?php

namespace Tests\Unit\Models;

use App\Models\Children;
use App\Models\UserGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserGroupTest extends TestCase
{
    use RefreshDatabase;

    public function test_automatically_generates_share_token_on_create(): void
    {
        $userGroup = UserGroup::create([
            'name' => 'テストグループ'
        ]);

        $this->assertNotNull($userGroup->share_token);
        $this->assertEquals(32, strlen($userGroup->share_token));
    }

    public function test_does_not_overwrite_existing_share_token(): void
    {
        $customToken = 'custom_token_12345678901234567890';
        
        $userGroup = UserGroup::create([
            'name' => 'テストグループ',
            'share_token' => $customToken
        ]);

        $this->assertEquals($customToken, $userGroup->share_token);
    }

    public function test_has_fillable_attributes(): void
    {
        $userGroup = new UserGroup();
        $expected = [
            'name',
            'share_token',
        ];

        $this->assertEquals($expected, $userGroup->getFillable());
    }

    public function test_has_many_children_relationship(): void
    {
        $userGroup = UserGroup::factory()->create();
        $children = Children::factory(3)->create([
            'user_group_id' => $userGroup->id
        ]);

        $this->assertCount(3, $userGroup->children);
        $this->assertInstanceOf(Children::class, $userGroup->children->first());
    }

    public function test_children_relationship_returns_empty_collection_when_no_children(): void
    {
        $userGroup = UserGroup::factory()->create();

        $this->assertCount(0, $userGroup->children);
    }

    public function test_can_access_children_through_relationship(): void
    {
        $userGroup = UserGroup::factory()->create();
        $child = Children::factory()->create([
            'user_group_id' => $userGroup->id,
            'name' => '太郎'
        ]);

        $retrievedChild = $userGroup->children()->first();
        
        $this->assertEquals($child->id, $retrievedChild->id);
        $this->assertEquals('太郎', $retrievedChild->name);
        $this->assertEquals($userGroup->id, $retrievedChild->user_group_id);
    }

    public function test_share_token_is_unique_across_multiple_creations(): void
    {
        $userGroup1 = UserGroup::create(['name' => 'グループ1']);
        $userGroup2 = UserGroup::create(['name' => 'グループ2']);

        $this->assertNotEquals($userGroup1->share_token, $userGroup2->share_token);
        $this->assertEquals(32, strlen($userGroup1->share_token));
        $this->assertEquals(32, strlen($userGroup2->share_token));
    }

    public function test_model_has_correct_table_name(): void
    {
        $userGroup = new UserGroup();
        
        $this->assertEquals('user_groups', $userGroup->getTable());
    }

    public function test_model_uses_timestamps(): void
    {
        $userGroup = UserGroup::create([
            'name' => 'テストグループ'
        ]);

        $this->assertNotNull($userGroup->created_at);
        $this->assertNotNull($userGroup->updated_at);
    }
}