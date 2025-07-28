<?php
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\WorkspaceController;
use Illuminate\Support\Facades\Route;

// Public auth routes
Route::prefix('auth')->name('auth.')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
});

// Protected routes (require auth)
Route::middleware('auth:sanctum')->group(function () {
    // Authenticated user info & logout
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::get('/me', [AuthController::class, 'me'])->name('me');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    });

    // Workspace routes
    Route::prefix('workspaces')->name('api.workspaces.')->group(function () {
        Route::get('/', [WorkspaceController::class, 'index'])->name('index');
        Route::post('/', [WorkspaceController::class, 'store'])->name('store');
        Route::get('/{workspace}', [WorkspaceController::class, 'show'])->name('show');
        Route::put('/{workspace}', [WorkspaceController::class, 'update'])->name('update');
        Route::delete('/{workspace}', [WorkspaceController::class, 'destroy'])->name('destroy');
        Route::patch('/{workspace}/activate', [WorkspaceController::class, 'activate'])->name('activate');
        Route::patch('/{workspace}/deactivate', [WorkspaceController::class, 'deactivate'])->name('deactivate');
        Route::post('/{workspace}/users', [WorkspaceController::class, 'assignUser'])->name('assign-user');
        Route::get('/{workspace}/teams', [TeamController::class, 'index'])->name('teams.index');
        Route::post('/{workspace}/teams', [TeamController::class, 'store'])->name('teams.store');
    });

    // Team routes
    Route::prefix('teams')->name('api.teams.')->group(function () {
        Route::get('/{team}', [TeamController::class, 'show'])->name('show');
        Route::put('/{team}', [TeamController::class, 'update'])->name('update');
        Route::delete('/{team}', [TeamController::class, 'destroy'])->name('destroy');
        Route::patch('/{team}/activate', [TeamController::class, 'activate'])->name('activate');
        Route::patch('/{team}/deactivate', [TeamController::class, 'deactivate'])->name('deactivate');
        Route::post('/{team}/users', [TeamController::class, 'assignUser'])->name('assign-user');
    });
});
