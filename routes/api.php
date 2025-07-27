<?php
use App\Http\Controllers\Api\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::prefix('workspaces')->group(function () {
    Route::get('/', [WorkspaceController::class, 'index']);
    Route::post('/', [WorkspaceController::class, 'store']);
    Route::get('{id}', [WorkspaceController::class, 'show']);
    Route::put('{workspace}', [WorkspaceController::class, 'update']);
    Route::delete('{workspace}', [WorkspaceController::class, 'destroy']);
});
