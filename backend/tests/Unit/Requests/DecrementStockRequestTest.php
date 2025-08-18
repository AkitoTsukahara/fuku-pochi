<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\DecrementStockRequest;
use App\Models\ClothingCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class DecrementStockRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorize_returns_true(): void
    {
        $request = new DecrementStockRequest();
        
        $this->assertTrue($request->authorize());
    }

    public function test_rules_returns_correct_validation_rules(): void
    {
        $request = new DecrementStockRequest();
        $expected = [
            'clothing_category_id' => 'required|integer|exists:clothing_categories,id',
            'decrement' => 'required|integer|min:1',
        ];
        
        $this->assertEquals($expected, $request->rules());
    }

    public function test_messages_returns_correct_custom_messages(): void
    {
        $request = new DecrementStockRequest();
        $expected = [
            'clothing_category_id.required' => '衣類カテゴリIDは必須です',
            'clothing_category_id.integer' => '衣類カテゴリIDは整数で入力してください',
            'clothing_category_id.exists' => '指定された衣類カテゴリが存在しません',
            'decrement.required' => '減少数は必須です',
            'decrement.integer' => '減少数は整数で入力してください',
            'decrement.min' => '減少数は1以上で入力してください',
        ];
        
        $this->assertEquals($expected, $request->messages());
    }

    public function test_validation_passes_with_valid_data(): void
    {
        $clothingCategory = ClothingCategory::create([
            'name' => 'ズボン',
            'icon_path' => '/icons/pants.svg',
            'sort_order' => 2
        ]);

        $data = [
            'clothing_category_id' => $clothingCategory->id,
            'decrement' => 2
        ];
        $rules = (new DecrementStockRequest())->rules();
        $messages = (new DecrementStockRequest())->messages();
        
        $validator = Validator::make($data, $rules, $messages);
        
        $this->assertFalse($validator->fails());
        $this->assertTrue($validator->passes());
    }

    public function test_validation_fails_without_clothing_category_id(): void
    {
        $data = ['decrement' => 1];
        $rules = (new DecrementStockRequest())->rules();
        $messages = (new DecrementStockRequest())->messages();
        
        $validator = Validator::make($data, $rules, $messages);
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('clothing_category_id', $validator->errors()->toArray());
        $this->assertContains('衣類カテゴリIDは必須です', $validator->errors()->get('clothing_category_id'));
    }

    public function test_validation_fails_without_decrement(): void
    {
        $clothingCategory = ClothingCategory::create([
            'name' => 'ズボン',
            'icon_path' => '/icons/pants.svg',
            'sort_order' => 2
        ]);

        $data = ['clothing_category_id' => $clothingCategory->id];
        $rules = (new DecrementStockRequest())->rules();
        $messages = (new DecrementStockRequest())->messages();
        
        $validator = Validator::make($data, $rules, $messages);
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('decrement', $validator->errors()->toArray());
        $this->assertContains('減少数は必須です', $validator->errors()->get('decrement'));
    }

    public function test_validation_fails_with_non_integer_clothing_category_id(): void
    {
        $data = [
            'clothing_category_id' => 'invalid',
            'decrement' => 1
        ];
        $rules = (new DecrementStockRequest())->rules();
        $messages = (new DecrementStockRequest())->messages();
        
        $validator = Validator::make($data, $rules, $messages);
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('clothing_category_id', $validator->errors()->toArray());
        $this->assertContains('衣類カテゴリIDは整数で入力してください', $validator->errors()->get('clothing_category_id'));
    }

    public function test_validation_fails_with_non_integer_decrement(): void
    {
        $clothingCategory = ClothingCategory::create([
            'name' => 'ズボン',
            'icon_path' => '/icons/pants.svg',
            'sort_order' => 2
        ]);

        $data = [
            'clothing_category_id' => $clothingCategory->id,
            'decrement' => 'invalid'
        ];
        $rules = (new DecrementStockRequest())->rules();
        $messages = (new DecrementStockRequest())->messages();
        
        $validator = Validator::make($data, $rules, $messages);
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('decrement', $validator->errors()->toArray());
        $this->assertContains('減少数は整数で入力してください', $validator->errors()->get('decrement'));
    }

    public function test_validation_fails_with_non_existent_clothing_category_id(): void
    {
        $data = [
            'clothing_category_id' => 99999,
            'decrement' => 1
        ];
        $rules = (new DecrementStockRequest())->rules();
        $messages = (new DecrementStockRequest())->messages();
        
        $validator = Validator::make($data, $rules, $messages);
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('clothing_category_id', $validator->errors()->toArray());
        $this->assertContains('指定された衣類カテゴリが存在しません', $validator->errors()->get('clothing_category_id'));
    }

    public function test_validation_fails_with_decrement_zero(): void
    {
        $clothingCategory = ClothingCategory::create([
            'name' => 'ズボン',
            'icon_path' => '/icons/pants.svg',
            'sort_order' => 2
        ]);

        $data = [
            'clothing_category_id' => $clothingCategory->id,
            'decrement' => 0
        ];
        $rules = (new DecrementStockRequest())->rules();
        $messages = (new DecrementStockRequest())->messages();
        
        $validator = Validator::make($data, $rules, $messages);
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('decrement', $validator->errors()->toArray());
        $this->assertContains('減少数は1以上で入力してください', $validator->errors()->get('decrement'));
    }

    public function test_validation_fails_with_negative_decrement(): void
    {
        $clothingCategory = ClothingCategory::create([
            'name' => 'ズボン',
            'icon_path' => '/icons/pants.svg',
            'sort_order' => 2
        ]);

        $data = [
            'clothing_category_id' => $clothingCategory->id,
            'decrement' => -1
        ];
        $rules = (new DecrementStockRequest())->rules();
        $messages = (new DecrementStockRequest())->messages();
        
        $validator = Validator::make($data, $rules, $messages);
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('decrement', $validator->errors()->toArray());
        $this->assertContains('減少数は1以上で入力してください', $validator->errors()->get('decrement'));
    }

    public function test_validation_passes_with_decrement_one(): void
    {
        $clothingCategory = ClothingCategory::create([
            'name' => 'ズボン',
            'icon_path' => '/icons/pants.svg',
            'sort_order' => 2
        ]);

        $data = [
            'clothing_category_id' => $clothingCategory->id,
            'decrement' => 1
        ];
        $rules = (new DecrementStockRequest())->rules();
        $messages = (new DecrementStockRequest())->messages();
        
        $validator = Validator::make($data, $rules, $messages);
        
        $this->assertFalse($validator->fails());
        $this->assertTrue($validator->passes());
    }

    public function test_validation_passes_with_large_decrement(): void
    {
        $clothingCategory = ClothingCategory::create([
            'name' => 'ズボン',
            'icon_path' => '/icons/pants.svg',
            'sort_order' => 2
        ]);

        $data = [
            'clothing_category_id' => $clothingCategory->id,
            'decrement' => 50
        ];
        $rules = (new DecrementStockRequest())->rules();
        $messages = (new DecrementStockRequest())->messages();
        
        $validator = Validator::make($data, $rules, $messages);
        
        $this->assertFalse($validator->fails());
        $this->assertTrue($validator->passes());
    }
}