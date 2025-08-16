<?php

namespace App\Http\Controllers\Api\Stock;

use App\Http\Controllers\Controller;
use App\Models\Children;
use App\Models\ClothingCategory;
use App\Models\StockItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class GetStockController extends Controller
{
    /**
     * Get stock items for a specific child with clothing category information.
     */
    public function __invoke(int $id): JsonResponse
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

        // Get all clothing categories ordered by sort_order
        $categories = ClothingCategory::orderBy('sort_order')->get();

        // Get existing stock items for this child
        $stockItems = StockItem::where('child_id', $id)
            ->with('clothingCategory')
            ->get()
            ->keyBy('clothing_category_id');

        // Build the response data
        $stockData = [];
        foreach ($categories as $category) {
            $stockItem = $stockItems->get($category->id);
            
            $stockData[] = [
                'clothing_category_id' => $category->id,
                'clothing_category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'icon_path' => $category->icon_path,
                    'sort_order' => $category->sort_order,
                ],
                'current_count' => $stockItem ? $stockItem->current_count : 0,
                'stock_item_id' => $stockItem ? $stockItem->id : null,
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'ストック情報を取得しました',
            'data' => [
                'child_id' => $child->id,
                'child_name' => $child->name,
                'stock_items' => $stockData
            ]
        ], Response::HTTP_OK);
    }
}