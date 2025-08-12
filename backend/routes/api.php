<?php

use App\Http\Controllers\Api\GroupController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/test', function () {
    return response()->json(['message' => 'API is working!']);
});

// Group Management API
Route::post('/groups', [GroupController::class, 'store']);
Route::get('/groups/{token}', [GroupController::class, 'show']);