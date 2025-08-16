<?php

namespace App\Http\Controllers\Api\Children;

use App\Http\Controllers\Controller;
use App\Models\Children;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class DeleteChildController extends Controller
{
    /**
     * Remove the specified child.
     */
    public function __invoke(int $id): JsonResponse
    {
        $child = Children::find($id);

        if (!$child) {
            return response()->json([
                'success' => false,
                'message' => '指定された子どもが見つかりません',
                'data' => null
            ], Response::HTTP_NOT_FOUND);
        }

        $child->delete();

        return response()->json([
            'success' => true,
            'message' => '子どもが削除されました',
            'data' => null
        ], Response::HTTP_OK);
    }
}