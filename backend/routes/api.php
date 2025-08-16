<?php

use App\Http\Controllers\Api\Groups\CreateGroupController;
use App\Http\Controllers\Api\Groups\GetGroupController;
use App\Http\Controllers\Api\Children\GetChildrenController;
use App\Http\Controllers\Api\Children\CreateChildController;
use App\Http\Controllers\Api\Children\UpdateChildController;
use App\Http\Controllers\Api\Children\DeleteChildController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/test', function () {
    return response()->json(['message' => 'API is working!']);
});

// Group Management API - Single Action Controllers
Route::post('/groups', CreateGroupController::class);
Route::get('/groups/{token}', GetGroupController::class);

// Children Management API - Single Action Controllers
Route::get('/groups/{token}/children', GetChildrenController::class);
Route::post('/groups/{token}/children', CreateChildController::class);
Route::put('/children/{id}', UpdateChildController::class);
Route::delete('/children/{id}', DeleteChildController::class);