<?php

namespace Tests\Feature\Groups;

use App\Models\Children;
use App\Models\UserGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetGroupControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_group_with_valid_token(): void
    {
        $group = UserGroup::factory()->create([
            'name' => 'テストグループ'
        ]);

        $response = $this->getJson("/api/groups/{$group->share_token}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'グループ情報を取得しました',
                'data' => [
                    'id' => $group->id,
                    'name' => 'テストグループ',
                    'share_token' => $group->share_token,
                    'children' => []
                ]
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
    }

    public function test_can_get_group_with_children(): void
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

        $response = $this->getJson("/api/groups/{$group->share_token}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'グループ情報を取得しました',
                'data' => [
                    'id' => $group->id,
                    'name' => 'テストグループ',
                    'share_token' => $group->share_token,
                    'children' => [
                        [
                            'id' => $child1->id,
                            'name' => '太郎',
                            'user_group_id' => $group->id
                        ],
                        [
                            'id' => $child2->id,
                            'name' => '花子',
                            'user_group_id' => $group->id
                        ]
                    ]
                ]
            ]);

        $responseData = $response->json('data');
        $this->assertCount(2, $responseData['children']);
    }

    public function test_returns_404_for_non_existent_token(): void
    {
        $nonExistentToken = 'non_existent_token_12345678901234567890';

        $response = $this->getJson("/api/groups/{$nonExistentToken}");

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

        $response = $this->getJson("/api/groups/{$invalidToken}");

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => '指定されたトークンのグループが見つかりません',
                'data' => null
            ]);
    }

    public function test_returns_405_for_empty_token(): void
    {
        $response = $this->getJson("/api/groups/");

        $response->assertStatus(405);
    }

    public function test_response_structure_is_correct(): void
    {
        $group = UserGroup::factory()->create();

        $response = $this->getJson("/api/groups/{$group->share_token}");

        $response->assertStatus(200)
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

        $responseData = $response->json();
        $this->assertTrue($responseData['success']);
        $this->assertIsArray($responseData['data']['children']);
        $this->assertIsString($responseData['data']['share_token']);
    }
}