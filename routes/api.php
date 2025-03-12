<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::controller(AuthController::class)->group(function () {
    Route::post('register', 'register');
    Route::post('login', 'login')->middleware('throttle:' . env('RATE_LIMIT', 10) . ',' . env('RATE_LIMIT_TIME', 1));
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::controller(AuthController::class)->group(function () {
        Route::get('login-sessions/{user?}', 'loginSessions');
        Route::post('logout', 'logout');
        Route::delete('delete-session/{token}', 'deleteSession');
    });

    Route::apiResource('roles', RoleController::class);
    Route::post('roles/assign-to-user/{user}', [RoleController::class, 'assignToUser']);

    Route::controller(PermissionController::class)->prefix('permissions')->group(function () {
        Route::get('', 'index');
        Route::post('assign-to-user/{user}', 'assignToUser');
    });

    Route::controller(UserController::class)->prefix('users')->group(function () {
        Route::get('me', 'me');
        Route::patch('change-password', 'changePassword');
        Route::post('{user}/restore', 'restore');
        Route::delete('{user}/delete-permanently', 'forceDestroy');
        Route::patch('{user}/change-status', 'changeStatus');
        Route::post('{user}/reset-password', 'resetPassword');
    });
    Route::apiResource('users', UserController::class);
});
