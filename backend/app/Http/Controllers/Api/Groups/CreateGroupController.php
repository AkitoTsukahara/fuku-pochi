<?php

namespace App\Http\Controllers\Api\Groups;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateGroupRequest;
use App\Models\UserGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class CreateGroupController extends Controller
{
    /**
     * Store a newly created user group.
     */
    public function __invoke(CreateGroupRequest $request): JsonResponse
    {
        $userGroup = UserGroup::create([
            'name' => $request->validated()['name'],
        ]);

        $userGroup->load('children');

        return response()->json([
            'success' => true,
            'message' => 'グループが正常に作成されました',
            'data' => [
                'id' => $userGroup->id,
                'name' => $userGroup->name,
                'share_token' => $userGroup->share_token,
                'children' => $userGroup->children,
                'created_at' => $userGroup->created_at,
                'updated_at' => $userGroup->updated_at,
            ]
        ], Response::HTTP_CREATED);
    }
}
