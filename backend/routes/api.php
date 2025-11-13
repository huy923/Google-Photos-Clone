<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AlbumController;
use App\Http\Controllers\MediaFileController;
use App\Http\Controllers\MediaMetadataController;
use App\Http\Controllers\MediaCommentController;
use App\Http\Controllers\MediaFavoriteController;
use App\Http\Controllers\MediaTagController;
use App\Http\Controllers\MediaViewController;
use App\Http\Controllers\ShareController;
use App\Http\Controllers\ShareAccessController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\UserStorageController;

Route::get('/', function () {
    return response()->json([
        'message' => 'Google Photos Clone API',
        'version' => '1.0.0',
        'status' => 'active',
        'endpoints' => [
            'users' => '/api/users',
            'profiles' => '/api/profiles',
            'albums' => '/api/albums',
            'media-files' => '/api/media-files',
            'media-metadata' => '/api/media-metadata',
            'media-comments' => '/api/media-comments',
            'media-favorites' => '/api/media-favorites',
            'media-tags' => '/api/media-tags',
            'media-views' => '/api/media-views',
            'shares' => '/api/shares',
            'share-access' => '/api/share-access',
            'notifications' => '/api/notifications',
            'user-storage' => '/api/user-storage'
        ]
    ]);
});

// Authentication routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});

Route::apiResource('users', UserController::class);

Route::apiResource('profiles', ProfileController::class);

Route::apiResource('albums', AlbumController::class);

// Album media management
Route::post('/albums/{album}/add-media', [AlbumController::class, 'addMedia']);
Route::post('/albums/{album}/remove-media', [AlbumController::class, 'removeMedia']);

Route::apiResource('media-files', MediaFileController::class);

Route::apiResource('media-metadata', MediaMetadataController::class);

Route::apiResource('media-comments', MediaCommentController::class);

Route::apiResource('media-favorites', MediaFavoriteController::class);

Route::apiResource('media-tags', MediaTagController::class);

Route::apiResource('media-views', MediaViewController::class);

Route::apiResource('shares', ShareController::class);

Route::apiResource('share-access', ShareAccessController::class);

Route::apiResource('notifications', NotificationController::class);

Route::apiResource('user-storage', UserStorageController::class);