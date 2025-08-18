<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\IncrementStockRequest;
use App\Models\ClothingCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class IncrementStockRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorize_returns_true(): void
    {
        $request = new IncrementStockRequest();
        
        $this->assertTrue($request->authorize());
    }

    public function test_rules_returns_correct_validation_rules(): void
    {
        $request = new IncrementStockRequest();
        $expected = [
            'clothing_category_id' => 'required|integer|exists:clothing_categories,id',
            'increment' => 'required|integer|min:1',
        ];
        
        $this->assertEquals($expected, $request->rules());
    }

    public function test_messages_returns_correct_custom_messages(): void
    {
        $request = new IncrementStockRequest();
        $expected = [
            'clothing_category_id.required' => '衣類カテゴリIDは必須です',
            'clothing_category_id.integer' => '衣類カテゴリIDは整数で入力してください',
            'clothing_category_id.exists' => '指定された衣類カテゴリが存在しません',
            'increment.required' => '増加数は必須です',
            'increment.integer' => '増加数は整数で入力してください',
            'increment.min' => '増加数は1以上で入力してください',
        ];
        
        $this->assertEquals($expected, $request->messages());
    }

    public function test_validation_passes_with_valid_data(): void
    {
        $clothingCategory = ClothingCategory::create([
            'name' => 'Tシャツ',
            'icon_path' => '/icons/tshirt.svg',
            'sort_order' => 1
        ]);

        $data = [
            'clothing_category_id' => $clothingCategory->id,
            'increment' => 3
        ];
        $rules = (new IncrementStockRequest())->rules();
        $messages = (new IncrementStockRequest())->messages();
        
        $validator = Validator::make($data, $rules, $messages);
        
        $this->assertFalse($validator->fails());
        $this->assertTrue($validator->passes());
    }

    public function test_validation_fails_without_clothing_category_id(): void
    {
        $data = ['increment' => 1];
        $rules = (new IncrementStockRequest())->rules();
        $messages = (new IncrementStockRequest())->messages();
        
        $validator = Validator::make($data, $rules, $messages);
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('clothing_category_id', $validator->errors()->toArray());
        $this->assertContains('衣類カテゴリIDは必須です', $validator->errors()->get('clothing_category_id'));
    }

    public function test_validation_fails_without_increment(): void
    {
        $clothingCategory = ClothingCategory::create([
            'name' => 'Tシャツ',
            'icon_path' => '/icons/tshirt.svg',
            'sort_order' => 1
        ]);

        $data = ['clothing_category_id' => $clothingCategory->id];
        $rules = (new IncrementStockRequest())->rules();
        $messages = (new IncrementStockRequest())->messages();
        
        $validator = Validator::make($data, $rules, $messages);
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('increment', $validator->errors()->toArray());
        $this->assertContains('増加数は必須です', $validator->errors()->get('increment'));
    }

    public function test_validation_fails_with_non_integer_clothing_category_id(): void
    {
        $data = [
            'clothing_category_id' => 'invalid',
            'increment' => 1
        ];
        $rules = (new IncrementStockRequest())->rules();
        $messages = (new IncrementStockRequest())->messages();
        
        $validator = Validator::make($data, $rules, $messages);
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('clothing_category_id', $validator->errors()->toArray());
        $this->assertContains('衣類カテゴリIDは整数で入力してください', $validator->errors()->get('clothing_category_id'));
    }

    public function test_validation_fails_with_non_integer_increment(): void
    {
        $clothingCategory = ClothingCategory::create([
            'name' => 'Tシャツ',
            'icon_path' => '/icons/tshirt.svg',
            'sort_order' => 1
        ]);

        $data = [
            'clothing_category_id' => $clothingCategory->id,
            'increment' => 'invalid'
        ];
        $rules = (new IncrementStockRequest())->rules();
        $messages = (new IncrementStockRequest())->messages();
        
        $validator = Validator::make($data, $rules, $messages);
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('increment', $validator->errors()->toArray());
        $this->assertContains('増加数は整数で入力してください', $validator->errors()->get('increment'));
    }

    public function test_validation_fails_with_non_existent_clothing_category_id(): void
    {
        $data = [
            'clothing_category_id' => 99999,
            'increment' => 1
        ];
        $rules = (new IncrementStockRequest())->rules();
        $messages = (new IncrementStockRequest())->messages();
        
        $validator = Validator::make($data, $rules, $messages);
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('clothing_category_id', $validator->errors()->toArray());
        $this->assertContains('指定された衣類カテゴリが存在しません', $validator->errors()->get('clothing_category_id'));
    }

    public function test_validation_fails_with_increment_zero(): void
    {
        $clothingCategory = ClothingCategory::create([
            'name' => 'Tシャツ',
            'icon_path' => '/icons/tshirt.svg',
            'sort_order' => 1
        ]);

        $data = [
            'clothing_category_id' => $clothingCategory->id,
            'increment' => 0
        ];
        $rules = (new IncrementStockRequest())->rules();
        $messages = (new IncrementStockRequest())->messages();
        
        $validator = Validator::make($data, $rules, $messages);
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('increment', $validator->errors()->toArray());
        $this->assertContains('増加数は1以上で入力してください', $validator->errors()->get('increment'));
    }

    public function test_validation_fails_with_negative_increment(): void
    {
        $clothingCategory = ClothingCategory::create([
            'name' => 'Tシャツ',
            'icon_path' => '/icons/tshirt.svg',
            'sort_order' => 1
        ]);

        $data = [
            'clothing_category_id' => $clothingCategory->id,
            'increment' => -1
        ];
        $rules = (new IncrementStockRequest())->rules();
        $messages = (new IncrementStockRequest())->messages();
        
        $validator = Validator::make($data, $rules, $messages);
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('increment', $validator->errors()->toArray());
        $this->assertContains('増加数は1以上で入力してください', $validator->errors()->get('increment'));
    }

    public function test_validation_passes_with_increment_one(): void
    {
        $clothingCategory = ClothingCategory::create([
            'name' => 'Tシャツ',
            'icon_path' => '/icons/tshirt.svg',
            'sort_order' => 1
        ]);

        $data = [
            'clothing_category_id' => $clothingCategory->id,
            'increment' => 1
        ];
        $rules = (new IncrementStockRequest())->rules();
        $messages = (new IncrementStockRequest())->messages();
        
        $validator = Validator::make($data, $rules, $messages);
        
        $this->assertFalse($validator->fails());
        $this->assertTrue($validator->passes());
    }

    public function test_validation_passes_with_large_increment(): void
    {
        $clothingCategory = ClothingCategory::create([
            'name' => 'Tシャツ',
            'icon_path' => '/icons/tshirt.svg',
            'sort_order' => 1
        ]);

        $data = [
            'clothing_category_id' => $clothingCategory->id,
            'increment' => 100
        ];
        $rules = (new IncrementStockRequest())->rules();
        $messages = (new IncrementStockRequest())->messages();
        
        $validator = Validator::make($data, $rules, $messages);
        
        $this->assertFalse($validator->fails());
        $this->assertTrue($validator->passes());
    }
}