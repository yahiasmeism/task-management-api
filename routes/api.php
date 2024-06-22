<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectCommentController;
use App\Http\Controllers\ProjectInvitationController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskCommentController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Accept invitation to a project
Route::get('projects/{project_id}/accept/{invitee_id}', [ProjectInvitationController::class, 'acceptInvite'])->name('projects.accept_invitation');



// Authentication routes
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);


Route::middleware('auth:sanctum')->group(function () {

    //--------------------------- User routes -----------------------
    Route::get('/user', [UserController::class, 'user']);
    Route::get('/user/projects', [UserController::class, 'projects']);
    Route::get('/user/tasks', [UserController::class, 'tasks']);


    // ---------------- Project Routes ------------------
    Route::get('/projects/{project_id}/users', [ProjectController::class, 'usersInProject']);
    Route::get('/projects', [ProjectController::class, 'index']);
    Route::post('/projects', [ProjectController::class, 'store']);
    Route::get('/projects/{project_id}', [ProjectController::class, 'show']);
    Route::put('/projects/{project_id}', [ProjectController::class, 'update']);
    Route::delete('/projects/{project_id}', [ProjectController::class, 'destroy']);

    Route::put('/projects/{project_id}/users/{userId}/role', [ProjectController::class, 'updateUserRole']);
    Route::delete('/projects/{project_id}/users/{userId}', [ProjectController::class, 'removeUser']);

    // ----------- invite user to project route ------------
    Route::post('/projects/{project_id}/invite', [ProjectInvitationController::class, 'inviteUser']);

    // ---------------- Task Routes ------------------
    Route::post('projects/{project_id}/tasks', [TaskController::class, 'store']);
    Route::get('projects/{project_id}/tasks', [TaskController::class, 'index']);
    Route::get('projects/{project_id}/tasks/{task_id}', [TaskController::class, 'show']);
    Route::put('projects/{project_id}/tasks/{task_id}', [TaskController::class, 'update']);
    Route::delete('projects/{project_id}/tasks', [TaskController::class, 'destroy']);
    Route::put('projects/{project_id}/tasks/{task_id}/status', [TaskController::class, 'updateStatus']);
    Route::get('projects/{project_id}/users/{userId}/tasks', [TaskController::class, 'getUserTasksInProject']);

    // -------------- Project Comments ------------
    Route::get('/projects/{project_id}/comments', [ProjectCommentController::class, 'index']);
    Route::post('/projects/{project_id}/comments', [ProjectCommentController::class, 'store']);
    Route::put('/projects/{project_id}/comments/{comment_id}', [ProjectCommentController::class, 'update']);
    Route::delete('/projects/{project_id}/comments/{comment_id}', [ProjectCommentController::class, 'destroy']);
    
    
    // -------------- Tasks Comments ------------
    Route::get('/tasks/{task_id}/comments', [TaskCommentController::class, 'index']);
    Route::post('/tasks/{task_id}/comments', [TaskCommentController::class, 'store']);
    Route::put('/tasks/{task_id}/comments/{comment_id}', [TaskCommentController::class, 'update']);
    Route::delete('/tasks/{task_id}/comments/{comment_id}', [TaskCommentController::class, 'destroy']);
});


