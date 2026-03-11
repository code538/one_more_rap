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
use App\Http\Controllers\Api\FaqController;
use App\Http\Controllers\Api\ContactController;

// Public Route Start ======================================
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::post('/forgot-password', [AuthController::class, 'sendResetLink']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);


Route::get('/seo/{pageKey}', [SeoSettingController::class, 'show']);

Route::get('faqs', [FaqController::class, 'getFaqs']);
Route::post('contact', [ContactController::class, 'store']);

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
        Route::get('product-images/product/{product_id}', [ProductImageController::class, 'showByProduct']);
        Route::apiResource('product-images', ProductImageController::class)->only(['index','store','destroy']);
        Route::get('product-features/product/{product_id}', [ProductFeatureController::class, 'index']);
        Route::apiResource('product-features', ProductFeatureController::class)->only(['store','destroy']);
        Route::get('product-specifications/product/{product_id}', [ProductSpecificationController::class, 'index']);
        Route::apiResource('product-specifications', ProductSpecificationController::class)->only(['store','destroy']);
        Route::get('product-reviews/product/{product_id}', [ProductReviewController::class, 'showByProduct']);
        Route::apiResource('product-reviews', ProductReviewController::class);

        Route::get('faqs/all', [FaqController::class, 'index']);
        Route::post('faqs', [FaqController::class, 'store']);
        Route::get('faqs/{id}', [FaqController::class, 'show']);
        Route::put('faqs/{id}', [FaqController::class, 'update']);
        Route::delete('faqs/{id}', [FaqController::class, 'destroy']);

        Route::get('contacts', [ContactController::class, 'index']);
        Route::get('contacts/{id}', [ContactController::class, 'show']);
        Route::patch('contacts/status/{id}', [ContactController::class, 'updateStatus']);
        Route::delete('contacts/{id}', [ContactController::class, 'destroy']);


    });

   
    Route::middleware('role:user')->prefix('user')->group(function () {

        Route::get('/profile', function () {
            return ['message' => 'User area'];
        });

        // all user API here
    
    });




});
