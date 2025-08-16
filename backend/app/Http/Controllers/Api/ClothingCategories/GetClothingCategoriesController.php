<?php

namespace App\Http\Controllers\Api\ClothingCategories;

use App\Http\Controllers\Controller;
use App\Models\ClothingCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class GetClothingCategoriesController extends Controller
{
    /**
     * Get all clothing categories ordered by sort_order.
     */
    public function __invoke(): JsonResponse
    {
        // Cache clothing categories for 1 hour since they rarely change
        $categories = Cache::remember('clothing_categories', 3600, function () {
            return ClothingCategory::orderBy('sort_order', 'asc')->get();
        });

        return response()->json([
            'success' => true,
            'message' => '衣類カテゴリ一覧を取得しました',
            'data' => [
                'categories' => $categories->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'icon_path' => $category->icon_path,
                        'sort_order' => $category->sort_order,
                    ];
                })->toArray()
            ]
        ], Response::HTTP_OK);
    }
}