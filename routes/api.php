<?php

use App\Http\Controllers\API\CatalogueController;
use App\Http\Controllers\API\CustomerController;
use App\Http\Controllers\API\JWTAuthController;
use App\Http\Controllers\API\LivraisonController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\SettingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Middleware\JwtMiddleware;

Route::post('register', [JWTAuthController::class, 'register']);
Route::post('login', [JWTAuthController::class, 'loginCustomer']);
Route::post('login_admin', [JWTAuthController::class, 'login']);
Route::get('departements', [SettingController::class, 'departements']);
Route::get('cities/{departement_id}', [SettingController::class, 'cities']);
Route::middleware(['jwt.verify','jwt.auth'])->group(function () {
    Route::get('user', [JWTAuthController::class, 'getUser']);
    Route::post('logout', [JWTAuthController::class, 'logout']);
    Route::post('stocks', [CatalogueController::class, 'updateStock']);
    Route::post('products', [CatalogueController::class, 'storeProduct']);
    Route::post('products/{id}', [CatalogueController::class, 'updateProduct']);
    Route::post('imports/products', [CatalogueController::class, 'importProducts']);
    Route::post('categories', [CatalogueController::class, 'storeCategory']);
    Route::get('categories', [CatalogueController::class, 'categories']);
    Route::get('categories/all', [CatalogueController::class, 'all_categories']);
    Route::get('products', [CatalogueController::class, 'products']);
    Route::get('products/waiting', [CatalogueController::class, 'productsWaiting']);
    Route::get('products/publish/{id}', [CatalogueController::class, 'publishProduct']);
    Route::get('products/{id}', [CatalogueController::class, 'getProductByID']);
    Route::post('orders', [OrderController::class, 'storeOrder']);
    Route::get('orders/customer', [OrderController::class, 'ordersCustomer']);
    Route::get('orders', [OrderController::class, 'orders']);
    Route::get('orders/{id}', [OrderController::class, 'orderDetail']);
    Route::post('litiges', [OrderController::class, 'storeLitige']);
    Route::post('returns', [OrderController::class, 'storeReturn']);
    Route::get('litiges', [OrderController::class, 'getLitiges']);
    Route::get('returns', [OrderController::class, 'getReturns']);
    Route::get('litiges/customer', [CustomerController::class, 'getLitiges']);
    Route::get('returns/customer', [CustomerController::class, 'getReturns']);
    Route::get('orders/{id}/{status}/status', [OrderController::class, 'changeStatus']);
    Route::post('paiements', [OrderController::class, 'paiementFacture']);
    Route::get('customers', [SettingController::class, 'getCustomers']);
    Route::get('agents', [SettingController::class, 'getAgents']);
    Route::post('agents', [SettingController::class, 'storeAgent']);

    Route::put('/livraisons/{id}/expedier', [LivraisonController::class, 'marquerExpedie']);
    Route::put('/livraisons/{id}/confirmer', [LivraisonController::class, 'confirmerReception']);
    Route::post('/livraisons/{id}/probleme', [LivraisonController::class, 'signalerProbleme']);

});
