<?php

use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AuthController;
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

// Public routes
Route::prefix('v1')->group(function () {
    // Authentication routes
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register'])->name('api.register');
        Route::post('login', [AuthController::class, 'login'])->name('api.login');
        Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum')->name('api.logout');
        Route::get('user', [AuthController::class, 'user'])->middleware('auth:sanctum')->name('api.user');
    });

    // Public blog content routes
    Route::apiResource('posts', PostController::class)->only(['index', 'show']);
    Route::apiResource('categories', CategoryController::class)->only(['index', 'show']);
    Route::apiResource('tags', TagController::class)->only(['index', 'show']);
    
    // Get posts by category
    Route::get('categories/{category}/posts', [CategoryController::class, 'posts']);
    
    // Get posts by tag
    Route::get('tags/{tag}/posts', [TagController::class, 'posts']);
    
    // Get comments for a post
    Route::get('posts/{post}/comments', [CommentController::class, 'index']);
});

// Protected routes
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // User management (only update/destroy for self, no listing)
    Route::apiResource('users', UserController::class)->only(['update', 'destroy']);
    
    // Post management (authenticated users)
    Route::apiResource('posts', PostController::class)->except(['index', 'show']);
    
    // Comments
    Route::apiResource('comments', CommentController::class)->except(['index']);
    Route::apiResource('posts.comments', CommentController::class)->except(['index']);
    
    // Likes
    Route::post('posts/{post}/like', [PostController::class, 'like']);
    Route::delete('posts/{post}/like', [PostController::class, 'unlike']);
    
    // User's  liked posts
    Route::get('user/liked-posts', [UserController::class, 'likedPosts']);
    
    // User's comments
    Route::get('user/comments', [UserController::class, 'comments']);
});

// Admin routes
Route::prefix('v1/admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    // Full CRUD for all resources
    Route::apiResource('posts', PostController::class);
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('tags', TagController::class);
    Route::apiResource('users', UserController::class);
    Route::apiResource('comments', CommentController::class);
});