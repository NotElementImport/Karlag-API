<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\PriceController;
use App\Http\Controllers\PriceGroupController;
use App\Http\Controllers\RepressedController;
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
            Route::patch('/{id}',  [PostController::class, 'revert']);
            Route::post('/{slug}', [PostController::class, 'update']);
        });
        Route::get('', [PostController::class, 'index']);
        Route::get('/{slug}', [PostController::class, 'show']);
    });

    // Event:
    Route::prefix('event')->group(function() {
        // With Token:
        Route::middleware(WithToken::class)->group(function() {
            Route::post('', [EventController::class, 'store']);
            Route::delete('/{id}', [EventController::class, 'destroy']);
            Route::patch('/{id}',  [EventController::class, 'revert']);
            Route::post('/{slug}', [EventController::class, 'update']);
        });

        Route::get('', [EventController::class, 'index']);
        Route::get('/{slug}', [EventController::class, 'show']);
    });

    // Event:
    Route::prefix('repressed')->group(function() {
        // With Token:
        Route::middleware(WithToken::class)->group(function() {
            Route::post('', [RepressedController::class, 'store']);
            Route::delete('/{id}', [RepressedController::class, 'destroy']);
            Route::patch('/{id}',  [RepressedController::class, 'revert']);
            Route::post('/{slug}', [RepressedController::class, 'update']);
        });

        Route::get('', [RepressedController::class, 'index']);
        Route::get('/{slug}', [RepressedController::class, 'show']);
    });

    // Price:
    Route::get('price-list', [PriceController::class, 'cached']);
    Route::prefix('price')->group(function() {
        // With Token:
        Route::middleware(WithToken::class)->group(function() {
            Route::get('', [PriceController::class, 'index']);
            Route::post('', [PriceController::class, 'store']);
            Route::post('batch',   [PriceGroupController::class, 'batchStore']);
            Route::delete('/{id}', [PriceController::class, 'destroy']);
            Route::patch('/{id}',  [PriceController::class, 'revert']);
            Route::post('/{id}',   [PriceController::class, 'update']);
        });
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

    // Price Group:
    Route::prefix('gallery')->group(function() {
        Route::get('', [GalleryController::class, 'index']);
        Route::get('dirs', [GalleryController::class, 'dirs']);
        // With Token:
        Route::middleware(WithToken::class)->group(function() {
            Route::post('', [GalleryController::class, 'store']);
            Route::delete('', [GalleryController::class, 'destroy']);
        });
    });
});
