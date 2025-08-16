<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DecrementStockRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'clothing_category_id' => 'required|integer|exists:clothing_categories,id',
            'decrement' => 'required|integer|min:1',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'clothing_category_id.required' => '衣類カテゴリIDは必須です',
            'clothing_category_id.integer' => '衣類カテゴリIDは整数で入力してください',
            'clothing_category_id.exists' => '指定された衣類カテゴリが存在しません',
            'decrement.required' => '減少数は必須です',
            'decrement.integer' => '減少数は整数で入力してください',
            'decrement.min' => '減少数は1以上で入力してください',
        ];
    }
}