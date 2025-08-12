<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateGroupRequest;
use App\Models\UserGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class GroupController extends Controller
{
    /**
     * Store a newly created user group.
     */
    public function store(CreateGroupRequest $request): JsonResponse
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

    /**
     * Display the specified user group by share token.
     */
    public function show(string $token): JsonResponse
    {
        $userGroup = UserGroup::where('share_token', $token)
            ->with('children')
            ->first();

        if (!$userGroup) {
            return response()->json([
                'success' => false,
                'message' => '指定されたトークンのグループが見つかりません',
                'data' => null
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'message' => 'グループ情報を取得しました',
            'data' => [
                'id' => $userGroup->id,
                'name' => $userGroup->name,
                'share_token' => $userGroup->share_token,
                'children' => $userGroup->children,
                'created_at' => $userGroup->created_at,
                'updated_at' => $userGroup->updated_at,
            ]
        ], Response::HTTP_OK);
    }
}
