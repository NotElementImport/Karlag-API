<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\PriceController;
use App\Http\Middleware\WithToken;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->group(function() {
    // Auth:
    Route::prefix('auth')->group(function() {
        Route::post('login', [AuthController::class, 'login']);
    });

    // Post:
    Route::prefix('post')->group(function() {
        // With Token:
        Route::middleware(WithToken::class)->group(function() {
            Route::post('', [PostController::class, 'store']);
            Route::delete('/{slug}', [PostController::class, 'destroy']);
            Route::patch('/{slug}', [PostController::class, 'revert']);
            Route::post('/{slug}', [PostController::class, 'update']);
        });

        Route::get('/{slug}', [PostController::class, 'show']);
        Route::get('', [PostController::class, 'index']);
    });

    // Price:
    Route::prefix('price')->group(function() {
        // With Token:
        Route::middleware(WithToken::class)->group(function() {
            Route::post('', [PriceController::class, 'store']);
            Route::delete('/{id}', [PriceController::class, 'destroy']);
            Route::patch('/{id}', [PriceController::class, 'revert']);
            Route::post('/{id}', [PriceController::class, 'update']);
        });

        Route::get('', [PriceController::class, 'index']);
    });
});
