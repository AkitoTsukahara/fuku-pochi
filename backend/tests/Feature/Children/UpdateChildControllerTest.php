<?php

namespace Tests\Feature\Children;

use App\Models\Children;
use App\Models\UserGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateChildControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_update_child_with_valid_data(): void
    {
        $group = UserGroup::factory()->create();
        $child = Children::factory()->create([
            'user_group_id' => $group->id,
            'name' => '太郎'
        ]);

        $response = $this->putJson("/api/children/{$child->id}", [
            'name' => '太郎（更新）'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => '子ども情報が更新されました',
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
            'id' => $child->id,
            'user_group_id' => $group->id,
            'name' => '太郎（更新）'
        ]);

        $responseData = $response->json('data');
        $this->assertEquals($child->id, $responseData['id']);
        $this->assertEquals($group->id, $responseData['user_group_id']);
        $this->assertEquals('太郎（更新）', $responseData['name']);
    }

    public function test_cannot_update_child_without_name(): void
    {
        $group = UserGroup::factory()->create();
        $child = Children::factory()->create([
            'user_group_id' => $group->id,
            'name' => '太郎'
        ]);

        $response = $this->putJson("/api/children/{$child->id}", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name'])
            ->assertJson([
                'message' => '子どもの名前は必須です',
                'errors' => [
                    'name' => ['子どもの名前は必須です']
                ]
            ]);

        $this->assertDatabaseHas('children', [
            'id' => $child->id,
            'name' => '太郎'
        ]);
    }

    public function test_cannot_update_child_with_empty_name(): void
    {
        $group = UserGroup::factory()->create();
        $child = Children::factory()->create([
            'user_group_id' => $group->id,
            'name' => '太郎'
        ]);

        $response = $this->putJson("/api/children/{$child->id}", [
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

        $this->assertDatabaseHas('children', [
            'id' => $child->id,
            'name' => '太郎'
        ]);
    }

    public function test_cannot_update_child_with_name_longer_than_255_characters(): void
    {
        $group = UserGroup::factory()->create();
        $child = Children::factory()->create([
            'user_group_id' => $group->id,
            'name' => '太郎'
        ]);
        $longName = str_repeat('あ', 256);

        $response = $this->putJson("/api/children/{$child->id}", [
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

        $this->assertDatabaseHas('children', [
            'id' => $child->id,
            'name' => '太郎'
        ]);
    }

    public function test_can_update_child_with_name_exactly_255_characters(): void
    {
        $group = UserGroup::factory()->create();
        $child = Children::factory()->create([
            'user_group_id' => $group->id,
            'name' => '太郎'
        ]);
        $maxName = str_repeat('あ', 255);

        $response = $this->putJson("/api/children/{$child->id}", [
            'name' => $maxName
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => '子ども情報が更新されました',
            ]);

        $this->assertDatabaseHas('children', [
            'id' => $child->id,
            'name' => $maxName
        ]);
    }

    public function test_cannot_update_child_with_non_string_name(): void
    {
        $group = UserGroup::factory()->create();
        $child = Children::factory()->create([
            'user_group_id' => $group->id,
            'name' => '太郎'
        ]);

        $response = $this->putJson("/api/children/{$child->id}", [
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

        $this->assertDatabaseHas('children', [
            'id' => $child->id,
            'name' => '太郎'
        ]);
    }

    public function test_returns_404_for_non_existent_child_id(): void
    {
        $nonExistentId = 99999;

        $response = $this->putJson("/api/children/{$nonExistentId}", [
            'name' => '更新名'
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => '指定された子どもが見つかりません',
                'data' => null
            ]);
    }

    public function test_updated_at_changes_when_child_is_updated(): void
    {
        $group = UserGroup::factory()->create();
        $child = Children::factory()->create([
            'user_group_id' => $group->id,
            'name' => '太郎'
        ]);

        $originalUpdatedAt = $child->updated_at;

        sleep(1);

        $response = $this->putJson("/api/children/{$child->id}", [
            'name' => '太郎（更新）'
        ]);

        $response->assertStatus(200);

        $child->refresh();
        $this->assertNotEquals($originalUpdatedAt, $child->updated_at);
    }

    public function test_user_group_id_remains_unchanged_when_updating_child(): void
    {
        $group = UserGroup::factory()->create();
        $child = Children::factory()->create([
            'user_group_id' => $group->id,
            'name' => '太郎'
        ]);

        $response = $this->putJson("/api/children/{$child->id}", [
            'name' => '太郎（更新）'
        ]);

        $response->assertStatus(200);

        $responseData = $response->json('data');
        $this->assertEquals($group->id, $responseData['user_group_id']);

        $this->assertDatabaseHas('children', [
            'id' => $child->id,
            'user_group_id' => $group->id,
            'name' => '太郎（更新）'
        ]);
    }

    public function test_response_structure_is_correct(): void
    {
        $group = UserGroup::factory()->create();
        $child = Children::factory()->create([
            'user_group_id' => $group->id,
            'name' => '太郎'
        ]);

        $response = $this->putJson("/api/children/{$child->id}", [
            'name' => '太郎（更新）'
        ]);

        $response->assertStatus(200)
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