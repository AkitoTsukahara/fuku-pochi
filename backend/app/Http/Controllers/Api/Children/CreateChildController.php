<?php

namespace App\Http\Controllers\Api\Children;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateChildRequest;
use App\Models\UserGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class CreateChildController extends Controller
{
    /**
     * Store a newly created child in a specific group.
     */
    public function __invoke(CreateChildRequest $request, string $token): JsonResponse
    {
        $userGroup = UserGroup::where('share_token', $token)->first();

        if (!$userGroup) {
            return response()->json([
                'success' => false,
                'message' => '指定されたトークンのグループが見つかりません',
                'data' => null
            ], Response::HTTP_NOT_FOUND);
        }

        $child = $userGroup->children()->create([
            'name' => $request->validated()['name'],
        ]);

        return response()->json([
            'success' => true,
            'message' => '子どもが正常に登録されました',
            'data' => [
                'id' => $child->id,
                'user_group_id' => $child->user_group_id,
                'name' => $child->name,
                'created_at' => $child->created_at,
                'updated_at' => $child->updated_at,
            ]
        ], Response::HTTP_CREATED);
    }
}