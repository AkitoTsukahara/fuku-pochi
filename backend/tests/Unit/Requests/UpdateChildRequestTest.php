<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\UpdateChildRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateChildRequestTest extends TestCase
{
    public function test_authorize_returns_true(): void
    {
        $request = new UpdateChildRequest();
        
        $this->assertTrue($request->authorize());
    }

    public function test_rules_returns_correct_validation_rules(): void
    {
        $request = new UpdateChildRequest();
        $expected = [
            'name' => 'required|string|max:255',
        ];
        
        $this->assertEquals($expected, $request->rules());
    }

    public function test_messages_returns_correct_custom_messages(): void
    {
        $request = new UpdateChildRequest();
        $expected = [
            'name.required' => '子どもの名前は必須です',
            'name.string' => '子どもの名前は文字列で入力してください',
            'name.max' => '子どもの名前は255文字以内で入力してください',
        ];
        
        $this->assertEquals($expected, $request->messages());
    }

    public function test_validation_passes_with_valid_data(): void
    {
        $data = ['name' => '花子'];
        $rules = (new UpdateChildRequest())->rules();
        $messages = (new UpdateChildRequest())->messages();
        
        $validator = Validator::make($data, $rules, $messages);
        
        $this->assertFalse($validator->fails());
        $this->assertTrue($validator->passes());
    }

    public function test_validation_fails_without_name(): void
    {
        $data = [];
        $rules = (new UpdateChildRequest())->rules();
        $messages = (new UpdateChildRequest())->messages();
        
        $validator = Validator::make($data, $rules, $messages);
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
        $this->assertContains('子どもの名前は必須です', $validator->errors()->get('name'));
    }

    public function test_validation_fails_with_empty_name(): void
    {
        $data = ['name' => ''];
        $rules = (new UpdateChildRequest())->rules();
        $messages = (new UpdateChildRequest())->messages();
        
        $validator = Validator::make($data, $rules, $messages);
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
        $this->assertContains('子どもの名前は必須です', $validator->errors()->get('name'));
    }

    public function test_validation_fails_with_non_string_name(): void
    {
        $data = ['name' => 456];
        $rules = (new UpdateChildRequest())->rules();
        $messages = (new UpdateChildRequest())->messages();
        
        $validator = Validator::make($data, $rules, $messages);
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
        $this->assertContains('子どもの名前は文字列で入力してください', $validator->errors()->get('name'));
    }

    public function test_validation_fails_with_name_longer_than_255_characters(): void
    {
        $data = ['name' => str_repeat('あ', 256)];
        $rules = (new UpdateChildRequest())->rules();
        $messages = (new UpdateChildRequest())->messages();
        
        $validator = Validator::make($data, $rules, $messages);
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
        $this->assertContains('子どもの名前は255文字以内で入力してください', $validator->errors()->get('name'));
    }

    public function test_validation_passes_with_name_exactly_255_characters(): void
    {
        $data = ['name' => str_repeat('あ', 255)];
        $rules = (new UpdateChildRequest())->rules();
        $messages = (new UpdateChildRequest())->messages();
        
        $validator = Validator::make($data, $rules, $messages);
        
        $this->assertFalse($validator->fails());
        $this->assertTrue($validator->passes());
    }

    public function test_validation_passes_with_updated_name(): void
    {
        $data = ['name' => '更新された名前'];
        $rules = (new UpdateChildRequest())->rules();
        $messages = (new UpdateChildRequest())->messages();
        
        $validator = Validator::make($data, $rules, $messages);
        
        $this->assertFalse($validator->fails());
        $this->assertTrue($validator->passes());
    }
}