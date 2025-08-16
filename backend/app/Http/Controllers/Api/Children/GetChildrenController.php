<?php

namespace App\Http\Controllers\Api\Children;

use App\Http\Controllers\Controller;
use App\Models\UserGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class GetChildrenController extends Controller
{
    /**
     * Display a listing of children for a specific group.
     */
    public function __invoke(string $token): JsonResponse
    {
        $userGroup = UserGroup::where('share_token', $token)->first();

        if (!$userGroup) {
            return response()->json([
                'success' => false,
                'message' => '指定されたトークンのグループが見つかりません',
                'data' => null
            ], Response::HTTP_NOT_FOUND);
        }

        $children = $userGroup->children()->get();

        return response()->json([
            'success' => true,
            'message' => '子どもの一覧を取得しました',
            'data' => $children
        ], Response::HTTP_OK);
    }
}