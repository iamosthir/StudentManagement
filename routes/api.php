<?php

use App\Http\Controllers\Api\StudentAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Student Authentication Routes (Public)
Route::prefix('student')->group(function () {
    Route::post('/register', [StudentAuthController::class, 'register']);
    Route::post('/login', [StudentAuthController::class, 'login']);
});

// Student Protected Routes (Requires Authentication)
Route::middleware('auth:student')->prefix('student')->group(function () {
    Route::get('/me', [StudentAuthController::class, 'me']);
    Route::post('/logout', [StudentAuthController::class, 'logout']);
});

// Default user route (Sanctum)
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
