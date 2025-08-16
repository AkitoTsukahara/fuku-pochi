<?php

namespace App\Http\Controllers\Api\Stock;

use App\Http\Controllers\Controller;
use App\Http\Requests\IncrementStockRequest;
use App\Models\Children;
use App\Models\ClothingCategory;
use App\Models\StockItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class IncrementStockController extends Controller
{
    /**
     * Increment stock count for a specific child and clothing category.
     */
    public function __invoke(IncrementStockRequest $request, int $id): JsonResponse
    {

        // Check if child exists
        $child = Children::find($id);
        if (!$child) {
            return response()->json([
                'success' => false,
                'message' => '指定された子どもが見つかりません',
                'data' => null
            ], Response::HTTP_NOT_FOUND);
        }

        // Get clothing category (already validated to exist)
        $clothingCategory = ClothingCategory::find($request->clothing_category_id);

        // Find or create stock item
        $stockItem = StockItem::firstOrCreate(
            [
                'child_id' => $id,
                'clothing_category_id' => $request->clothing_category_id
            ],
            [
                'current_count' => 0
            ]
        );

        // Increment the count
        $stockItem->current_count += $request->increment;
        $stockItem->save();

        return response()->json([
            'success' => true,
            'message' => 'ストック数を増加しました',
            'data' => [
                'child_id' => $child->id,
                'child_name' => $child->name,
                'stock_item' => [
                    'id' => $stockItem->id,
                    'clothing_category_id' => $clothingCategory->id,
                    'clothing_category' => [
                        'id' => $clothingCategory->id,
                        'name' => $clothingCategory->name,
                        'icon_path' => $clothingCategory->icon_path,
                        'sort_order' => $clothingCategory->sort_order,
                    ],
                    'current_count' => $stockItem->current_count,
                ]
            ]
        ], Response::HTTP_OK);
    }
}