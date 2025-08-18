<?php

namespace App\Http\Controllers\Api\Children;

use App\Http\Controllers\Controller;
use App\Models\Children;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class GetChildController extends Controller
{
    /**
     * Display the specified child.
     */
    public function __invoke(string $id): JsonResponse
    {
        $child = Children::find($id);

        if (!$child) {
            return response()->json([
                'success' => false,
                'message' => '指定されたIDの子どもが見つかりません',
                'data' => null
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'message' => '子どもの情報を取得しました',
            'data' => [
                'id' => $child->id,
                'name' => $child->name,
                'user_group_id' => $child->user_group_id,
                'created_at' => $child->created_at,
                'updated_at' => $child->updated_at,
            ]
        ], Response::HTTP_OK);
    }
}