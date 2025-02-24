<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RoleController;
use Illuminate\Support\Facades\Route;

Route::controller(AuthController::class)->group(function () {
    Route::post('register', 'register');
    Route::post('login', 'login');
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::controller(AuthController::class)->group(function () {
        Route::get('/login-sessions', 'loginSessions');
        Route::post('/logout/{tokenId?}', 'logout')->where('tokenId', '[0-9]+');
    });

    Route::apiResource('roles', RoleController::class);
});
