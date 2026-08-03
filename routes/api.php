<?php

use Spatie\FlareClient\Api;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExternalApiController;

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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
Route::controller(ExternalApiController::class)->prefix('v1')->group(function () {
    // Get all Categories
    Route::get('/category', 'getCategories');
    // Get all products
    Route::get('/products/{category_slug}', 'getProductsByCategory');
    // Get variations
    Route::get('/variations/{product_slug}', 'getVariationsByProductSlug');
    // Verify
    Route::post('/verify-biller', 'verifyBiller');

    Route::middleware('api-auth')->group(function () {
        // Get balance
        Route::get('/get-balance', 'getBalance');
        // Query
        Route::post('/query', 'makePayment');
        // Requery
        Route::post('/re-query', 'queryTransaction');
    });
});
