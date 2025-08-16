<?php

namespace App\Http\Controllers\Api\Stock;

use App\Http\Controllers\Controller;
use App\Http\Requests\DecrementStockRequest;
use App\Models\Children;
use App\Models\ClothingCategory;
use App\Models\StockItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class DecrementStockController extends Controller
{
    /**
     * Decrement stock count for a specific child and clothing category.
     */
    public function __invoke(DecrementStockRequest $request, int $id): JsonResponse
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

        // Find existing stock item
        $stockItem = StockItem::where('child_id', $id)
            ->where('clothing_category_id', $request->clothing_category_id)
            ->first();

        if (!$stockItem) {
            return response()->json([
                'success' => false,
                'message' => '指定されたアイテムのストックが存在しません',
                'data' => null
            ], Response::HTTP_NOT_FOUND);
        }

        // Check if decrement would result in negative count
        $newCount = $stockItem->current_count - $request->decrement;
        if ($newCount < 0) {
            return response()->json([
                'success' => false,
                'message' => 'ストック数が0未満になるため減少できません',
                'data' => [
                    'current_count' => $stockItem->current_count,
                    'requested_decrement' => $request->decrement
                ]
            ], Response::HTTP_BAD_REQUEST);
        }

        // Decrement the count
        $stockItem->current_count = $newCount;
        $stockItem->save();

        return response()->json([
            'success' => true,
            'message' => 'ストック数を減少しました',
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