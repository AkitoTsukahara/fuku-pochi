<?php

namespace Tests\Feature\Groups;

use App\Models\UserGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateGroupControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_group_with_valid_data(): void
    {
        $response = $this->postJson('/api/groups', [
            'name' => 'テストグループ'
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'グループが正常に作成されました',
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

        $this->assertDatabaseHas('user_groups', [
            'name' => 'テストグループ'
        ]);

        $group = UserGroup::first();
        $this->assertNotEmpty($group->share_token);
        $this->assertEquals(32, strlen($group->share_token));
    }

    public function test_cannot_create_group_without_name(): void
    {
        $response = $this->postJson('/api/groups', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name'])
            ->assertJson([
                'message' => 'グループ名は必須です',
                'errors' => [
                    'name' => ['グループ名は必須です']
                ]
            ]);

        $this->assertDatabaseCount('user_groups', 0);
    }

    public function test_cannot_create_group_with_empty_name(): void
    {
        $response = $this->postJson('/api/groups', [
            'name' => ''
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name'])
            ->assertJson([
                'message' => 'グループ名は必須です',
                'errors' => [
                    'name' => ['グループ名は必須です']
                ]
            ]);

        $this->assertDatabaseCount('user_groups', 0);
    }

    public function test_cannot_create_group_with_name_longer_than_255_characters(): void
    {
        $longName = str_repeat('あ', 256);

        $response = $this->postJson('/api/groups', [
            'name' => $longName
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name'])
            ->assertJson([
                'message' => 'グループ名は255文字以内で入力してください',
                'errors' => [
                    'name' => ['グループ名は255文字以内で入力してください']
                ]
            ]);

        $this->assertDatabaseCount('user_groups', 0);
    }

    public function test_can_create_group_with_name_exactly_255_characters(): void
    {
        $maxName = str_repeat('あ', 255);

        $response = $this->postJson('/api/groups', [
            'name' => $maxName
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'グループが正常に作成されました',
            ]);

        $this->assertDatabaseHas('user_groups', [
            'name' => $maxName
        ]);
    }

    public function test_cannot_create_group_with_non_string_name(): void
    {
        $response = $this->postJson('/api/groups', [
            'name' => 123
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name'])
            ->assertJson([
                'message' => 'グループ名は文字列で入力してください',
                'errors' => [
                    'name' => ['グループ名は文字列で入力してください']
                ]
            ]);

        $this->assertDatabaseCount('user_groups', 0);
    }

    public function test_response_includes_empty_children_array(): void
    {
        $response = $this->postJson('/api/groups', [
            'name' => 'テストグループ'
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'children' => []
                ]
            ]);
    }
}