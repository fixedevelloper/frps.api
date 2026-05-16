<?php

use App\Http\Controllers\API\AdvantageController;
use App\Http\Controllers\API\auth\AuthController;
use App\Http\Controllers\API\auth\ProfilController;
use App\Http\Controllers\API\CatalogueController;
use App\Http\Controllers\API\CustomerController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\EnterStockController;
use App\Http\Controllers\API\LitigeController;
use App\Http\Controllers\API\LitigeMessageController;
use App\Http\Controllers\API\LivraisonController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\PasswordController;
use App\Http\Controllers\API\RolePermissionController;
use App\Http\Controllers\API\SettingController;
use App\Http\Controllers\API\TransporteurController;
use App\Http\Controllers\API\UserController;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| ROUTES PUBLIQUES (Sans Auth)
|--------------------------------------------------------------------------
*/
//Route::post('register', [JWTAuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login'])->name('login');
Route::post('login_admin', [AuthController::class, 'loginAdmin']);

Route::prefix('password')->group(function () {
    Route::post('forgot', [PasswordController::class, 'sendResetLinkEmail']);
    Route::post('reset', [PasswordController::class, 'reset']);
});

Route::get('email/verify/{id}', [PasswordController::class, 'verify'])
    ->name('verification.verify')->middleware('signed');

Route::get('departements', [SettingController::class, 'departements']);
Route::get('cities/{departement_id}', [SettingController::class, 'cities']);
Route::get('/user-permissions', function() {
    return response()->json([
        'roles' => auth()->user()->getRoleNames(),
        'permissions' => auth()->user()->getAllPermissions()->pluck('name'),
    ]);
});

/*
|--------------------------------------------------------------------------
| ROUTES PROTÉGÉES (Middleware JWT & Verified)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    // --- AUTH & PROFILE ---
    Route::get('profile', [ProfilController::class, 'profile']);
    Route::post('change_password', [ProfilController::class, 'changePassword']);
    Route::post('profile/update', [ProfilController::class, 'updateProfile']);
    Route::post('logout', [AuthController::class, 'logout']);

    // --- DASHBOARD & ANALYTICS ---
    Route::prefix('dashboard')->group(function () {
        Route::get('statistics', [DashboardController::class, 'getStatistics']);
        Route::get('sales-chart', [DashboardController::class, 'salesChart']);
        Route::get('sale-days-chart', [DashboardController::class, 'salesWeekChart']);
        Route::get('last-orders', [DashboardController::class, 'lastOrders']);
    });

    // --- CATALOGUE (Produits & Catégories) ---
    Route::prefix('products')->group(function () {
        Route::get('/', [CatalogueController::class, 'products']);
        Route::post('/', [CatalogueController::class, 'storeProduct']);
        Route::get('waiting', [CatalogueController::class, 'productsWaiting']);
        Route::get('publish/{id}', [CatalogueController::class, 'publishProduct']);
        Route::get('{id}', [CatalogueController::class, 'getProductByID']);
        Route::put('{id}', [CatalogueController::class, 'updateProduct']);
    });

    Route::prefix('categories')->group(function () {
        Route::get('/', [CatalogueController::class, 'categories']);
        Route::post('/', [CatalogueController::class, 'storeCategory']);
        Route::get('all', [CatalogueController::class, 'all_categories']);
        Route::get('{id}/show', [CatalogueController::class, 'showCategory']);
        Route::put('{id}', [CatalogueController::class, 'updateCategory']);
    });

    Route::post('stocks', [CatalogueController::class, 'updateStock']);
    Route::post('imports/products', [CatalogueController::class, 'importProducts']);
    Route::apiResource('enter-stocks', EnterStockController::class);

    // --- COMMANDES (Orders) ---
    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'orders']);
        Route::post('/', [OrderController::class, 'storeOrder']);
        Route::get('customer', [OrderController::class, 'ordersCustomer']);
        Route::get('{id}', [OrderController::class, 'orderDetail']);
        Route::get('{id}/{status}/status', [OrderController::class, 'changeStatus']);
        Route::post('update-item', [OrderController::class, 'updateQuantity']);
        Route::put('{id}/assign-transporteur', [OrderController::class, 'assignTransporteur']);
    });
    Route::post('paiements', [OrderController::class, 'paiementFacture']);
    Route::post('simulation/paiements', [OrderController::class, 'paiementFactureMobile']);

    // --- LITIGES & RETOURS ---
    Route::prefix('litiges')->group(function () {
        Route::get('customer', [LitigeController::class, 'getLitiges']);
        Route::patch('{id}/status', [LitigeController::class, 'updateStatus']);
        Route::post('messages', [LitigeMessageController::class, 'store']);
        Route::get('{litigeId}/messages', [LitigeMessageController::class, 'getMessages']);
    });
    Route::apiResource('litiges', LitigeController::class);

    Route::prefix('returns')->group(function () {
        Route::get('/', [OrderController::class, 'getReturns']);
        Route::post('/', [OrderController::class, 'storeReturn']);
        Route::get('customer', [CustomerController::class, 'getReturns']);
        Route::delete('{id}', [CustomerController::class, 'destroyReturn']);
    });

    // --- LOGISTIQUE (Livraisons & Transporteurs) ---
    Route::prefix('livraisons')->group(function () {
        Route::put('{id}/expedier', [LivraisonController::class, 'marquerExpedie']);
        Route::put('{id}/confirmer', [LivraisonController::class, 'confirmerReception']);
        Route::post('{id}/probleme', [LivraisonController::class, 'signalerProbleme']);
    });

    Route::prefix('transporteurs')->group(function () {
        Route::get('/', [TransporteurController::class, 'index']);
        Route::post('/', [TransporteurController::class, 'store']);
        Route::put('/{id}', [TransporteurController::class, 'storeOrUpdate']);
        Route::delete('/{id}', [TransporteurController::class, 'deleteTransporteur']);
        Route::put('/vehicules/{id}', [TransporteurController::class, 'updateVehicule']);
        Route::get('vehicules', [TransporteurController::class, 'vehicules']);
        Route::get('vehicules/{id}', [TransporteurController::class, 'vehiculeShow']);
        Route::post('vehicules', [TransporteurController::class, 'vehiculestore']);
        Route::get('chauffeurs', [TransporteurController::class, 'chauffeurs']);
        Route::get('{id}', [TransporteurController::class, 'show']);
        Route::delete('vehicules/{id}', [TransporteurController::class, 'destroyVehicule']);
    });

    // --- GESTION UTILISATEURS (Clients & Agents) ---
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'storeOrUpdate']);
        Route::get('{id}', [UserController::class, 'show']);
        Route::post('{id}', [UserController::class, 'storeOrUpdate']);
        Route::delete('{id}', [UserController::class, 'destroy']);
    });

    Route::prefix('agents')->group(function () {
        Route::get('/', [SettingController::class, 'getAgents']);
        Route::post('/', [SettingController::class, 'storeAgent']);
        Route::put('/{id}', [SettingController::class, 'updateAgent']);
        Route::patch('{id}/status', [SettingController::class, 'updateStatus']);
    });

    Route::prefix('customers')->group(function () {
        Route::get('/', [SettingController::class, 'getCustomers']);
        Route::get('info', [CustomerController::class, 'getInfo']);
        Route::post('update', [CustomerController::class, 'updateInfo']);
        Route::get('{id}/advantages', [AdvantageController::class, 'customerAdvantages']);
    });

    // --- AVANTAGES (CRUD & Toggle) ---
    Route::prefix('advantages')->group(function () {
        Route::get('/', [AdvantageController::class, 'index']);
        Route::post('/', [AdvantageController::class, 'store']);
        Route::get('{id}', [AdvantageController::class, 'show']);
        Route::put('{id}', [AdvantageController::class, 'update']);
        Route::delete('{id}', [AdvantageController::class, 'destroy']);
        Route::patch('{id}/toggle', [AdvantageController::class, 'toggle']);
    });

    // --- PARAMÈTRES & SYSTÈME ---
    Route::get('catalogues', [CustomerController::class, 'index']);
    Route::get('settings', [SettingController::class, 'show']);
    Route::post('settings', [SettingController::class, 'update']);
    Route::get('notifications', [SettingController::class, 'notifications']);
    Route::get('/settings/roles-permissions', [RolePermissionController::class, 'index']);
    Route::post('/settings/roles', [RolePermissionController::class, 'storeRole']);
    Route::put('/settings/roles/{roleId}/permissions', [RolePermissionController::class, 'updatePermissions']);
});

