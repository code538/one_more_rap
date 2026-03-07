<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Website\SeoSettingController;
use App\Http\Controllers\Api\Website\WebsiteSettingController;
use App\Http\Controllers\Api\Website\CategoryController;
use App\Http\Controllers\Api\Website\SubcategoryController;
use App\Http\Controllers\Api\Website\ProductController;
use App\Http\Controllers\Api\Website\ProductImageController;
use App\Http\Controllers\Api\Website\ProductFeatureController;
use App\Http\Controllers\Api\Website\ProductSpecificationController;
use App\Http\Controllers\Api\Website\ProductReviewController;

// Public Route Start ======================================
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::post('/forgot-password', [AuthController::class, 'sendResetLink']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);


Route::get('/seo/{pageKey}', [SeoSettingController::class, 'show']);

/*
|--------------------------------------------------------------------------
| Protected routes (Sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

 
    Route::middleware('role:admin')->prefix('admin')->group(function () {

        Route::get('/dashboard', function () {
            return ['message' => 'Admin area'];
        });

        // all admin API here

        Route::get('/seo', [SeoSettingController::class, 'index']);
        Route::post('/seo', [SeoSettingController::class, 'store']);
        Route::get('/seo/{id}', [SeoSettingController::class, 'edit']);
        Route::put('/seo/{id}', [SeoSettingController::class, 'update']);
        Route::delete('/seo/{id}', [SeoSettingController::class, 'destroy']);

        Route::post('/website-settings', [WebsiteSettingController::class, 'store']);
        Route::get('/website-settings', [WebsiteSettingController::class, 'edit']);
        Route::post('/website-settings-update/{id}', [WebsiteSettingController::class, 'update']);

        Route::apiResource('categories', CategoryController::class);
        Route::apiResource('subcategories', SubcategoryController::class);
        
        Route::apiResource('products', ProductController::class);
        Route::apiResource('product-images', ProductImageController::class)->only(['index','store','destroy']);
        Route::apiResource('product-features', ProductFeatureController::class)->only(['store','destroy']);
        Route::apiResource('product-specifications', ProductSpecificationController::class)->only(['store','destroy']);
        Route::apiResource('product-reviews', ProductReviewController::class);


    });

   
    Route::middleware('role:user')->prefix('user')->group(function () {

        Route::get('/profile', function () {
            return ['message' => 'User area'];
        });

        // all user API here
    
    });




});
