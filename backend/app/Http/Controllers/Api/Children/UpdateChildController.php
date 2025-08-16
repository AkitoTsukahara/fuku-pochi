<?php

namespace App\Http\Controllers\Api\Children;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateChildRequest;
use App\Models\Children;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class UpdateChildController extends Controller
{
    /**
     * Update the specified child.
     */
    public function __invoke(UpdateChildRequest $request, int $id): JsonResponse
    {
        $child = Children::find($id);

        if (!$child) {
            return response()->json([
                'success' => false,
                'message' => '指定された子どもが見つかりません',
                'data' => null
            ], Response::HTTP_NOT_FOUND);
        }

        $child->update([
            'name' => $request->validated()['name'],
        ]);

        return response()->json([
            'success' => true,
            'message' => '子ども情報が更新されました',
            'data' => [
                'id' => $child->id,
                'user_group_id' => $child->user_group_id,
                'name' => $child->name,
                'created_at' => $child->created_at,
                'updated_at' => $child->updated_at,
            ]
        ], Response::HTTP_OK);
    }
}