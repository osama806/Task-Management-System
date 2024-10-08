<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\TaskController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->controller(AuthController::class)->group(function () {
        Route::middleware(['auth:api', 'refresh.token'])->group(function () {
            Route::get('user/profile', 'show');
            Route::put('users/{id}', 'updateProfile');
            Route::post('logout', 'logout');
            Route::delete('user/delete', 'deleteUser');
            Route::get('users/deleted-users', 'showDeletedUsers');
            Route::post('user/restore', 'restoreUser');
            Route::post('user/permanently-delete', 'forceDeleteUser');
        });
        Route::post('users', 'register');
        Route::post('login', 'login');
    });

    Route::middleware(['auth:api', 'refresh.token'])->group(function () {
        Route::get('/users', [AuthController::class, 'index']);
        Route::apiResource('/tasks', TaskController::class)->except(['index', 'show']);
        Route::controller(TaskController::class)->group(function () {
            Route::post('/tasks/{id}/assign', 'assign');
            Route::post('tasks/{id}/delivery', 'taskDelivery');
            Route::post('/task/{id}/restore', 'restore');
            Route::get('/user/my-tasks', 'myTasks');
            Route::get('/tasks/deleted-tasks', 'showDeletedTasks');
            Route::post('/task/{id}/permanently-delete', 'forceDeleteTask');
        });
    });
    Route::apiResource('/tasks', TaskController::class)->only(['index', 'show']);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
