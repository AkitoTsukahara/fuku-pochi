<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\CreateGroupRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class CreateGroupRequestTest extends TestCase
{
    public function test_authorize_returns_true(): void
    {
        $request = new CreateGroupRequest();
        
        $this->assertTrue($request->authorize());
    }

    public function test_rules_returns_correct_validation_rules(): void
    {
        $request = new CreateGroupRequest();
        $expected = [
            'name' => 'required|string|max:255',
        ];
        
        $this->assertEquals($expected, $request->rules());
    }

    public function test_messages_returns_correct_custom_messages(): void
    {
        $request = new CreateGroupRequest();
        $expected = [
            'name.required' => 'グループ名は必須です',
            'name.string' => 'グループ名は文字列で入力してください',
            'name.max' => 'グループ名は255文字以内で入力してください',
        ];
        
        $this->assertEquals($expected, $request->messages());
    }

    public function test_validation_passes_with_valid_data(): void
    {
        $data = ['name' => 'テストグループ'];
        $rules = (new CreateGroupRequest())->rules();
        $messages = (new CreateGroupRequest())->messages();
        
        $validator = Validator::make($data, $rules, $messages);
        
        $this->assertFalse($validator->fails());
        $this->assertTrue($validator->passes());
    }

    public function test_validation_fails_without_name(): void
    {
        $data = [];
        $rules = (new CreateGroupRequest())->rules();
        $messages = (new CreateGroupRequest())->messages();
        
        $validator = Validator::make($data, $rules, $messages);
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
        $this->assertContains('グループ名は必須です', $validator->errors()->get('name'));
    }

    public function test_validation_fails_with_empty_name(): void
    {
        $data = ['name' => ''];
        $rules = (new CreateGroupRequest())->rules();
        $messages = (new CreateGroupRequest())->messages();
        
        $validator = Validator::make($data, $rules, $messages);
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
        $this->assertContains('グループ名は必須です', $validator->errors()->get('name'));
    }

    public function test_validation_fails_with_non_string_name(): void
    {
        $data = ['name' => 123];
        $rules = (new CreateGroupRequest())->rules();
        $messages = (new CreateGroupRequest())->messages();
        
        $validator = Validator::make($data, $rules, $messages);
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
        $this->assertContains('グループ名は文字列で入力してください', $validator->errors()->get('name'));
    }

    public function test_validation_fails_with_name_longer_than_255_characters(): void
    {
        $data = ['name' => str_repeat('あ', 256)];
        $rules = (new CreateGroupRequest())->rules();
        $messages = (new CreateGroupRequest())->messages();
        
        $validator = Validator::make($data, $rules, $messages);
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
        $this->assertContains('グループ名は255文字以内で入力してください', $validator->errors()->get('name'));
    }

    public function test_validation_passes_with_name_exactly_255_characters(): void
    {
        $data = ['name' => str_repeat('あ', 255)];
        $rules = (new CreateGroupRequest())->rules();
        $messages = (new CreateGroupRequest())->messages();
        
        $validator = Validator::make($data, $rules, $messages);
        
        $this->assertFalse($validator->fails());
        $this->assertTrue($validator->passes());
    }

    public function test_validation_passes_with_single_character_name(): void
    {
        $data = ['name' => 'A'];
        $rules = (new CreateGroupRequest())->rules();
        $messages = (new CreateGroupRequest())->messages();
        
        $validator = Validator::make($data, $rules, $messages);
        
        $this->assertFalse($validator->fails());
        $this->assertTrue($validator->passes());
    }

    public function test_validation_passes_with_japanese_characters(): void
    {
        $data = ['name' => 'テストグループ名前'];
        $rules = (new CreateGroupRequest())->rules();
        $messages = (new CreateGroupRequest())->messages();
        
        $validator = Validator::make($data, $rules, $messages);
        
        $this->assertFalse($validator->fails());
        $this->assertTrue($validator->passes());
    }

    public function test_validation_passes_with_mixed_characters(): void
    {
        $data = ['name' => 'Test テスト 123'];
        $rules = (new CreateGroupRequest())->rules();
        $messages = (new CreateGroupRequest())->messages();
        
        $validator = Validator::make($data, $rules, $messages);
        
        $this->assertFalse($validator->fails());
        $this->assertTrue($validator->passes());
    }
}