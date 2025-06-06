<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});
// Auth routes
Route::post('/register', [App\Http\Controllers\Auth\RegisteredUserController::class, 'store'])
    ->name('register');

Route::post('/login', [App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'store'])
    ->name('login');

Route::post('/logout', [App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'destroy'])
    ->name('logout');

// Team routes
Route::post('/team-store', [App\Http\Controllers\TeamController::class, 'store'])
    ->name('team.store');

Route::get('/team-list/{user}', [App\Http\Controllers\TeamController::class, 'index'])
    ->name('team.index');

Route::post('/team-join', [App\Http\Controllers\TeamController::class, 'join_team'])
    ->name('team.join');

Route::get('/all-teams', [App\Http\Controllers\TeamController::class, 'all_teams'])
    ->name('team.all');

Route::get('user-activities/{user}', function ($user) {
    $activities = Spatie\Activitylog\Models\Activity::with(['causer', 'subject'])
        ->where('causer_id', $user->id)
        ->get();

    return response()->json(['activities' => $activities], 200);
})->name('activity.log');

// Task routes
Route::post('/task-assign', [App\Http\Controllers\TaskController::class, 'store'])
    ->name('task.assign');
Route::get('/tasks/{user}', [App\Http\Controllers\TaskController::class, 'index'])
    ->name('task.index');
Route::post('/task-update/{task}', [App\Http\Controllers\TaskController::class, 'update'])
    ->name('task.update');

// Upload routes
Route::post('/upload', [App\Http\Controllers\UploadController::class, 'store'])
    ->name('upload.store');
Route::get('/uploads', [App\Http\Controllers\UploadController::class, 'index'])
    ->name('upload.index');
Route::get('/download/{task}', [App\Http\Controllers\UploadController::class, 'download'])
    ->name('upload.download');
Route::delete('/upload/{task}', [App\Http\Controllers\UploadController::class, 'destroy'])
    ->name('upload.destroy');

Route::get('user-team-activities/{user}', function (\App\Models\User $user) {
    // Get all team IDs where user is leader or member
    $teamIds = \App\Models\Team::where('leader_id', $user->id)
        ->orWhereHas('members', function ($query) use ($user) {
            $query->where('users.id', $user->id);
        })
        ->pluck('id');

    // Get activities for those teams
    $activities = \Spatie\Activitylog\Models\Activity::with(['causer', 'subject'])
        ->where('subject_type', \App\Models\Team::class)
        ->whereIn('subject_id', $teamIds)
        ->latest()
        ->get();

    return response()->json(['activities' => $activities], 200);
})->name('user.team.activities');
