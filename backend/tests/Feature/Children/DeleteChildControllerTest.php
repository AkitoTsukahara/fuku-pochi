<?php

namespace Tests\Feature\Children;

use App\Models\Children;
use App\Models\UserGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteChildControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_delete_existing_child(): void
    {
        $group = UserGroup::factory()->create();
        $child = Children::factory()->create([
            'user_group_id' => $group->id,
            'name' => '太郎'
        ]);

        $response = $this->deleteJson("/api/children/{$child->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => '子どもが削除されました',
                'data' => null
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data'
            ]);

        $this->assertDatabaseMissing('children', [
            'id' => $child->id
        ]);

        $this->assertDatabaseCount('children', 0);
    }

    public function test_returns_404_for_non_existent_child_id(): void
    {
        $nonExistentId = 99999;

        $response = $this->deleteJson("/api/children/{$nonExistentId}");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => '指定された子どもが見つかりません',
                'data' => null
            ]);
    }

    public function test_can_delete_child_from_group_with_multiple_children(): void
    {
        $group = UserGroup::factory()->create();
        $child1 = Children::factory()->create([
            'user_group_id' => $group->id,
            'name' => '太郎'
        ]);
        $child2 = Children::factory()->create([
            'user_group_id' => $group->id,
            'name' => '花子'
        ]);

        $response = $this->deleteJson("/api/children/{$child1->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => '子どもが削除されました',
                'data' => null
            ]);

        $this->assertDatabaseMissing('children', [
            'id' => $child1->id
        ]);

        $this->assertDatabaseHas('children', [
            'id' => $child2->id,
            'name' => '花子'
        ]);

        $this->assertDatabaseCount('children', 1);
    }

    public function test_deleting_child_does_not_affect_other_groups(): void
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

        $response = $this->deleteJson("/api/children/{$child1->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('children', [
            'id' => $child1->id
        ]);

        $this->assertDatabaseHas('children', [
            'id' => $child2->id,
            'name' => 'グループ2の子'
        ]);

        $this->assertDatabaseCount('children', 1);
    }

    public function test_response_structure_is_correct(): void
    {
        $group = UserGroup::factory()->create();
        $child = Children::factory()->create([
            'user_group_id' => $group->id,
            'name' => '太郎'
        ]);

        $response = $this->deleteJson("/api/children/{$child->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data'
            ]);

        $responseData = $response->json();
        $this->assertTrue($responseData['success']);
        $this->assertNull($responseData['data']);
        $this->assertIsString($responseData['message']);
    }

    public function test_deleting_non_existent_child_returns_proper_json_structure(): void
    {
        $nonExistentId = 99999;

        $response = $this->deleteJson("/api/children/{$nonExistentId}");

        $response->assertStatus(404)
            ->assertJsonStructure([
                'success',
                'message',
                'data'
            ]);

        $responseData = $response->json();
        $this->assertFalse($responseData['success']);
        $this->assertNull($responseData['data']);
        $this->assertEquals('指定された子どもが見つかりません', $responseData['message']);
    }

    public function test_delete_operation_is_idempotent(): void
    {
        $group = UserGroup::factory()->create();
        $child = Children::factory()->create([
            'user_group_id' => $group->id,
            'name' => '太郎'
        ]);

        $response1 = $this->deleteJson("/api/children/{$child->id}");
        $response1->assertStatus(200);

        $response2 = $this->deleteJson("/api/children/{$child->id}");
        $response2->assertStatus(404);

        $this->assertDatabaseMissing('children', [
            'id' => $child->id
        ]);
    }

    // Note: Test for stock_items deletion will be added once StockItem model is implemented
    // public function test_deleting_child_also_deletes_related_stock_items(): void
    // {
    //     // This test should be implemented when StockItem model and relations are ready
    // }
}