<?php

namespace Tests\Feature\Children;

use App\Models\Children;
use App\Models\UserGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateChildControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_child_with_valid_data(): void
    {
        $group = UserGroup::factory()->create([
            'name' => 'テストグループ'
        ]);

        $response = $this->postJson("/api/groups/{$group->share_token}/children", [
            'name' => '太郎'
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => '子どもが正常に登録されました',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'user_group_id',
                    'name',
                    'created_at',
                    'updated_at',
                ]
            ]);

        $this->assertDatabaseHas('children', [
            'user_group_id' => $group->id,
            'name' => '太郎'
        ]);

        $responseData = $response->json('data');
        $this->assertEquals($group->id, $responseData['user_group_id']);
        $this->assertEquals('太郎', $responseData['name']);
    }

    public function test_cannot_create_child_without_name(): void
    {
        $group = UserGroup::factory()->create();

        $response = $this->postJson("/api/groups/{$group->share_token}/children", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name'])
            ->assertJson([
                'message' => '子どもの名前は必須です',
                'errors' => [
                    'name' => ['子どもの名前は必須です']
                ]
            ]);

        $this->assertDatabaseCount('children', 0);
    }

    public function test_cannot_create_child_with_empty_name(): void
    {
        $group = UserGroup::factory()->create();

        $response = $this->postJson("/api/groups/{$group->share_token}/children", [
            'name' => ''
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name'])
            ->assertJson([
                'message' => '子どもの名前は必須です',
                'errors' => [
                    'name' => ['子どもの名前は必須です']
                ]
            ]);

        $this->assertDatabaseCount('children', 0);
    }

    public function test_cannot_create_child_with_name_longer_than_255_characters(): void
    {
        $group = UserGroup::factory()->create();
        $longName = str_repeat('あ', 256);

        $response = $this->postJson("/api/groups/{$group->share_token}/children", [
            'name' => $longName
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name'])
            ->assertJson([
                'message' => '子どもの名前は255文字以内で入力してください',
                'errors' => [
                    'name' => ['子どもの名前は255文字以内で入力してください']
                ]
            ]);

        $this->assertDatabaseCount('children', 0);
    }

    public function test_can_create_child_with_name_exactly_255_characters(): void
    {
        $group = UserGroup::factory()->create();
        $maxName = str_repeat('あ', 255);

        $response = $this->postJson("/api/groups/{$group->share_token}/children", [
            'name' => $maxName
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => '子どもが正常に登録されました',
            ]);

        $this->assertDatabaseHas('children', [
            'user_group_id' => $group->id,
            'name' => $maxName
        ]);
    }

    public function test_cannot_create_child_with_non_string_name(): void
    {
        $group = UserGroup::factory()->create();

        $response = $this->postJson("/api/groups/{$group->share_token}/children", [
            'name' => 123
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name'])
            ->assertJson([
                'message' => '子どもの名前は文字列で入力してください',
                'errors' => [
                    'name' => ['子どもの名前は文字列で入力してください']
                ]
            ]);

        $this->assertDatabaseCount('children', 0);
    }

    public function test_returns_404_for_non_existent_group_token(): void
    {
        $nonExistentToken = 'non_existent_token_12345678901234567890';

        $response = $this->postJson("/api/groups/{$nonExistentToken}/children", [
            'name' => '太郎'
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => '指定されたトークンのグループが見つかりません',
                'data' => null
            ]);

        $this->assertDatabaseCount('children', 0);
    }

    public function test_can_create_multiple_children_in_same_group(): void
    {
        $group = UserGroup::factory()->create();

        $response1 = $this->postJson("/api/groups/{$group->share_token}/children", [
            'name' => '太郎'
        ]);

        $response2 = $this->postJson("/api/groups/{$group->share_token}/children", [
            'name' => '花子'
        ]);

        $response1->assertStatus(201);
        $response2->assertStatus(201);

        $this->assertDatabaseHas('children', [
            'user_group_id' => $group->id,
            'name' => '太郎'
        ]);

        $this->assertDatabaseHas('children', [
            'user_group_id' => $group->id,
            'name' => '花子'
        ]);

        $this->assertDatabaseCount('children', 2);
    }

    public function test_response_structure_is_correct(): void
    {
        $group = UserGroup::factory()->create();

        $response = $this->postJson("/api/groups/{$group->share_token}/children", [
            'name' => '太郎'
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'user_group_id',
                    'name',
                    'created_at',
                    'updated_at',
                ]
            ]);

        $responseData = $response->json();
        $this->assertTrue($responseData['success']);
        $this->assertIsInt($responseData['data']['id']);
        $this->assertIsInt($responseData['data']['user_group_id']);
        $this->assertIsString($responseData['data']['name']);
    }
}