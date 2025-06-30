<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CouncilController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

Route::get('/oauth/{provider}', [AuthController::class, 'redirectToProvider']);
Route::get('/oauth/{provider}/callback', [AuthController::class, 'handleProviderCallback']);

Route::get('/totals', [CouncilController::class, 'totals']);
Route::get('/councils/search', [CouncilController::class, 'search']);

