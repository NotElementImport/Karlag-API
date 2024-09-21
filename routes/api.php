<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\PriceController;
use App\Http\Controllers\PriceGroupController;
use App\Http\Middleware\WithToken;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->group(function() {
    // Auth:
    Route::prefix('auth')->group(function() {
        Route::post('login', [AuthController::class, 'login']);
    });

    // Files:
    Route::prefix('files')->group(function() {
        // With Token:
        Route::middleware(WithToken::class)->group(function() {
            Route::get('/list', [FileController::class, 'index']);
            Route::post('/upload/images', [FileController::class, 'uploadImages']);
            Route::post('/upload/documents', [FileController::class, 'uploadDocumets']);
        });
    });

    // Post:
    Route::prefix('post')->group(function() {
        // With Token:
        Route::middleware(WithToken::class)->group(function() {
            Route::post('', [PostController::class, 'store']);
            Route::delete('/{id}', [PostController::class, 'destroy']);
            Route::patch('/{id}', [PostController::class, 'revert']);
            Route::post('/{slug}', [PostController::class, 'update']);
        });

        Route::get('/{slug}', [PostController::class, 'show']);
        Route::get('', [PostController::class, 'index']);
    });

    Route::get('price-list', [PriceController::class, 'cached']);

    // Price:
    Route::prefix('price')->group(function() {
        // With Token:
        Route::middleware(WithToken::class)->group(function() {
            Route::post('', [PriceController::class, 'store']);
            Route::post('batch', [PriceGroupController::class, 'batchStore']);
            Route::delete('/{id}', [PriceController::class, 'destroy']);
            Route::patch('/{id}', [PriceController::class, 'revert']);
            Route::post('/{id}', [PriceController::class, 'update']);
        });

        Route::get('', [PriceController::class, 'index']);
    });

    // Price Group:
    Route::prefix('price-group')->group(function() {
        // With Token:
        Route::middleware(WithToken::class)->group(function() {
            Route::get('', [PriceGroupController::class, 'index']);
            Route::post('', [PriceGroupController::class, 'store']);
            Route::delete('/{id}', [PriceGroupController::class, 'destroy']);
            Route::patch('/{id}', [PriceGroupController::class, 'revert']);
            Route::post('/{id}', [PriceController::class, 'update']);
        });
    });
});
