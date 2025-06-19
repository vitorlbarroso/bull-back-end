<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\OrderBumpController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductOfferingController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CelCashController;
use App\Http\Controllers\UserBankAccountController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WithdrawalRequestsController;
use App\Http\Controllers\MembersAreaOffersIntegrationsController;
use App\Models\WithdrawalRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MembersAreaController;
use App\Http\Controllers\ModulesController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\CelcashWebhooksController;
use App\Http\Controllers\BanksCodeController;
use \App\Http\Controllers\PixelsController;

Route::prefix('user')->group(function() {
    Route::get('/forgotPassword/validateToken', [AuthController::class, 'verify_token']);
    Route::post('/', [AuthController::class, 'store']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgotPassword', [AuthController::class, 'forgot_password']);
    Route::put('/forgotPassword', [AuthController::class, 'define_forgot_password']);

    Route::group(['middleware' => ['auth:sanctum']], function () {
        Route::get('/current', [AuthController::class, 'show']);
        Route::delete('/logout', [AuthController::class, 'destroy']);
        Route::get('/dashboard', [UserController::class, 'dashboard']);
    });
});

Route::prefix('products')->middleware('auth:sanctum')->group(function() {
    Route::get('/', [ProductController::class, 'index']);
    Route::get('/offers/{product_id}', [ProductController::class, 'get_offers']);
    Route::get('/enum/categories', [ProductController::class, 'get_product_categories']);
    Route::get('/enum/types', [ProductController::class, 'get_product_types']);
    Route::post('/', [ProductController::class, 'store']);
    Route::post('/duplicate/{id}', [ProductController::class, 'duplicate']);
    Route::put('/{id}', [ProductController::class, 'update']);
    Route::delete('/{id}', [ProductController::class, 'destroy']);
});

Route::prefix('offers')->middleware('auth:sanctum')->group(function() {
    Route::get('/', [ProductOfferingController::class, 'index']);
    Route::get('/{id}', [ProductOfferingController::class, 'show']);
    Route::post('/', [ProductOfferingController::class, 'store']);
    Route::put('/{id}', [ProductOfferingController::class, 'update']);
    Route::delete('/{id}', [ProductOfferingController::class, 'destroy']);
});

Route::prefix('media')->middleware('auth:sanctum')->group(function() {
    Route::post('/uploadfile', MediaController::class);
});

Route::prefix('checkouts')->group(function() {
    Route::get('/pay/{checkout_hash}', [CheckoutController::class, 'get_public_checkout']);
    Route::get('/verify_pay/{payment_id}', [CheckoutController::class, 'verify_pay']);

    Route::group(['middleware' => ['auth:sanctum']], function () {
        Route::get('/{hashIdentifier}', [CheckoutController::class, 'show']);
        Route::get('/order_bumps_to_checkout/{hashIdentifier}', [CheckoutController::class, 'order_bumps_to_checkout']);
        Route::post('/', [CheckoutController::class, 'store']);
        Route::post('/add_review', [CheckoutController::class, 'add_review']);
        Route::put('/{id}', [CheckoutController::class, 'update']);
        Route::put('/active_checkout/{checkout_id}', [CheckoutController::class, 'active_checkout']);
        Route::delete('/remove_review/{reviewId}', [CheckoutController::class, 'remove_review']);
        Route::delete('/{id}', [CheckoutController::class, 'destroy']);
    });
});

Route::prefix('order_bumps')->middleware('auth:sanctum')->group(function() {
    Route::post('/', [OrderBumpController::class, 'store']);
    Route::delete('/{id}', [OrderBumpController::class, 'destroy']);
});
Route::prefix('members')->middleware('auth:sanctum')->group(function() {
    Route::post('/', [MembersAreaController::class, 'store']);
    Route::post('/duplicate/{id}', [MembersAreaController::class, 'duplicate']);
    Route::get('/', [MembersAreaController::class, 'index']);
    Route::get('/{membership_id}', [MembersAreaController::class, 'getModulesByMembership']);
    Route::put('/{id}', [MembersAreaController::class, 'update']);
    Route::delete('/{id}', [MembersAreaController::class, 'destroy']);
});

Route::prefix('modules')->middleware('auth:sanctum')->group(function() {
    Route::post('/', [ModulesController::class, 'store']);
    Route::post('/duplicate/{id}', [ModulesController::class, 'duplicate']);
    Route::get('/', [ModulesController::class, 'index']);
    Route::get('/{modules_id}', [ModulesController::class, 'getLessonsByModules']);
    Route::put('/{id}', [ModulesController::class, 'update']);
    Route::delete('/{id}', [ModulesController::class, 'destroy']);
});

Route::prefix('lessons')->middleware('auth:sanctum')->group(function() {
    Route::post('/upload', [\App\Services\MediaService::class, 'initiateUpload']);
    Route::post('/pre-url', [\App\Services\MediaService::class, 'getPresignedUrl']);
    Route::post('/complete-upload', [\App\Services\MediaService::class, 'completeUpload']);
    Route::post('/duplicate/{id}', [LessonController::class, 'duplicate']);
    Route::post('/', [LessonController::class, 'store']);
    Route::get('/', [LessonController::class, 'index']);
    Route::patch('/{id}', [LessonController::class, 'update']);
    Route::delete('/{id}', [LessonController::class, 'destroy']);
});

Route::prefix('integration')->middleware('auth:sanctum')->group(function() {
    Route::post('/', [MembersAreaOffersIntegrationsController::class, 'store']);
    Route::get('/{membership_id}', [MembersAreaOffersIntegrationsController::class, 'show']);
    Route::get('/{membership_id}/offers', [MembersAreaOffersIntegrationsController::class, 'list_offers']);
    Route::put('/', [MembersAreaOffersIntegrationsController::class, 'update']);
    Route::delete('/membership/{membership_id}/offer/{offer_id}', [MembersAreaOffersIntegrationsController::class, 'destroy']);
});
Route::prefix('payments')->group(function() {
    Route::post('/pix', [CelCashController::class, 'generate_payment_pix']);

    Route::group(['middleware' => ['auth:sanctum']], function () {
        Route::post('/create_user_cpf', [CelCashController::class, 'create_user_cpf']);
        Route::post('/create_user_cnpj', [CelCashController::class, 'create_user_cnpj']);
        Route::get('/unpaid_payments', [CelCashController::class, 'unpaid_payments']);
        Route::get('/all_payments', [CelCashController::class, 'all_payments']);
        Route::get('/paid_payments', [CelCashController::class, 'paid_payments']);
        Route::get('/chargebacks_payments', [CelCashController::class, 'chargebacks_payments']);
    });
});

Route::prefix('bank')->middleware('auth:sanctum')->group(function() {
    Route::get('/code_banks', [BanksCodeController::class, 'index']);
    Route::get('/accounts', [UserBankAccountController::class, 'get_accounts']);
    Route::post('/create_account', [UserBankAccountController::class, 'create_account']);
    Route::put('/update_status/{id}', [UserBankAccountController::class, 'update_status']);
    Route::delete('/delete_account/{id}', [UserBankAccountController::class, 'delete_account']);
});

Route::prefix('withdraws')->middleware('auth:sanctum')->group(function() {
    Route::get('/infos', [WithdrawalRequestsController::class, 'get_withdraw_infos']);
    Route::get('/requests', [WithdrawalRequestsController::class, 'withdraws_requests']);
    Route::post('/request', [WithdrawalRequestsController::class, 'request_withdrawal'])->middleware('withdrawal.rate.limit');
});

Route::prefix('webhooks')->group(function() {
    Route::prefix('reflow')->middleware('celcash.webhook')->group(function() {
        Route::post('/documents', [CelcashWebhooksController::class, 'documents']);
        Route::post('/transactions', [CelcashWebhooksController::class, 'transactions']);
    });

    Route::prefix('zendry')->group(function() {
        Route::post('/transactions', [CelcashWebhooksController::class, 'transactions_zendry']);
    });
    Route::prefix('venit')->group(function() {
        Route::post('/transactions', [CelcashWebhooksController::class, 'transactions_venit']);
    });
});

Route::prefix('pixel')->middleware('auth:sanctum')->group(function() {
    Route::post('/', [PixelsController::class, 'store']);
    Route::get('/{offer_id}', [PixelsController::class, 'show']);
});

Route::prefix('pixel')->group(function() {
    Route::post('/event', [PixelsController::class, 'send']);
});
