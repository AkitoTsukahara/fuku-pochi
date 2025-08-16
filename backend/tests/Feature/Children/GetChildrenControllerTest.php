<?php

namespace Tests\Feature\Children;

use App\Models\Children;
use App\Models\UserGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetChildrenControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_empty_children_list_with_valid_token(): void
    {
        $group = UserGroup::factory()->create([
            'name' => 'テストグループ'
        ]);

        $response = $this->getJson("/api/groups/{$group->share_token}/children");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => '子どもの一覧を取得しました',
                'data' => []
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data'
            ]);
    }

    public function test_can_get_children_list_with_valid_token(): void
    {
        $group = UserGroup::factory()->create([
            'name' => 'テストグループ'
        ]);

        $child1 = Children::factory()->create([
            'user_group_id' => $group->id,
            'name' => '太郎'
        ]);

        $child2 = Children::factory()->create([
            'user_group_id' => $group->id,
            'name' => '花子'
        ]);

        $response = $this->getJson("/api/groups/{$group->share_token}/children");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => '子どもの一覧を取得しました',
                'data' => [
                    [
                        'id' => $child1->id,
                        'user_group_id' => $group->id,
                        'name' => '太郎'
                    ],
                    [
                        'id' => $child2->id,
                        'user_group_id' => $group->id,
                        'name' => '花子'
                    ]
                ]
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'user_group_id',
                        'name',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);

        $responseData = $response->json('data');
        $this->assertCount(2, $responseData);
    }

    public function test_returns_404_for_non_existent_token(): void
    {
        $nonExistentToken = 'non_existent_token_12345678901234567890';

        $response = $this->getJson("/api/groups/{$nonExistentToken}/children");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => '指定されたトークンのグループが見つかりません',
                'data' => null
            ]);
    }

    public function test_returns_404_for_invalid_token_format(): void
    {
        $invalidToken = 'invalid';

        $response = $this->getJson("/api/groups/{$invalidToken}/children");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => '指定されたトークンのグループが見つかりません',
                'data' => null
            ]);
    }

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

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => '子どもの一覧を取得しました',
                'data' => [
                    [
                        'id' => $child1->id,
                        'user_group_id' => $group1->id,
                        'name' => 'グループ1の子'
                    ]
                ]
            ]);

        $responseData = $response->json('data');
        $this->assertCount(1, $responseData);
        $this->assertEquals('グループ1の子', $responseData[0]['name']);
    }

    public function test_response_structure_is_correct(): void
    {
        $group = UserGroup::factory()->create();

        $response = $this->getJson("/api/groups/{$group->share_token}/children");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data'
            ]);

        $responseData = $response->json();
        $this->assertTrue($responseData['success']);
        $this->assertIsArray($responseData['data']);
    }
}