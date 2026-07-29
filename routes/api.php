<?php

use App\Http\Controllers\Api\Admin\AccountController as AdminAccountController;
use App\Http\Controllers\Api\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Api\Admin\GiftcodeController as AdminGiftcodeController;
use App\Http\Controllers\Api\Admin\ItemController as AdminItemController;
use App\Http\Controllers\Api\Admin\ShopController as AdminShopController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BxhController;
use App\Http\Controllers\Api\ForumController;
use App\Http\Controllers\Api\GiftcodeController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\PostInteractionController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\SePayController;
use App\Http\Controllers\Api\TopupCardController;
use App\Http\Controllers\Api\TopupController;
use Illuminate\Support\Facades\Route;

Route::prefix('api')->group(function () {
    Route::get('/home', [HomeController::class, 'index']);
    Route::get('/bxh', [BxhController::class, 'index']);
    Route::get('/giftcodes', [GiftcodeController::class, 'index']);
    Route::get('/forum/posts', [ForumController::class, 'index']);
    Route::get('/forum/posts/{post}', [ForumController::class, 'show'])->whereNumber('post');
    Route::get('/forum/posts/{post}/comments', [ForumController::class, 'comments'])->whereNumber('post');
    Route::post('/forum/posts/{post}/share', [ForumController::class, 'share'])->whereNumber('post');
    Route::get('/posts/{slug}', [HomeController::class, 'postDetail']);
    Route::get('/posts/{slug}/engagement', [PostInteractionController::class, 'engagement']);
    Route::get('/posts/{slug}/comments', [PostInteractionController::class, 'comments']);

    Route::post('/auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:client-login');
    Route::post('/auth/register', [AuthController::class, 'register'])
        ->middleware('throttle:client-register');
    Route::get('/topup/atm-config', [TopupController::class, 'atmConfig']);

    Route::middleware('game.auth')->group(function () {
        Route::post('/auth/change-password', [AuthController::class, 'changePassword']);
        Route::post('/auth/activate', [AuthController::class, 'activate']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
        Route::post('/activate', [AuthController::class, 'activate']);
        Route::get('/profile', [ProfileController::class, 'profile']);
        Route::get('/topup/atm-payment-code', [TopupController::class, 'atmPaymentCode']);
        Route::get('/topup/history', [TopupController::class, 'history']);
        Route::post('/topup/card', [TopupCardController::class, 'submit']);
        Route::get('/topup/card/history', [TopupCardController::class, 'userHistory']);
        Route::post('/posts/{slug}/like', [PostInteractionController::class, 'togglePostLike']);
        Route::post('/comments/{comment}/like', [PostInteractionController::class, 'toggleCommentLike'])->whereNumber('comment');

        Route::put('/forum/posts/{post}', [ForumController::class, 'update'])->whereNumber('post');
        Route::delete('/forum/posts/{post}', [ForumController::class, 'destroy'])->whereNumber('post');
        Route::post('/forum/posts/read-all', [ForumController::class, 'markAllRead']);
        Route::post('/forum/posts/{post}/read', [ForumController::class, 'markRead'])->whereNumber('post');
        Route::post('/forum/posts/{post}/reaction', [ForumController::class, 'toggleReaction'])->whereNumber('post');
        Route::post('/forum/posts/{post}/save', [ForumController::class, 'toggleSave'])->whereNumber('post');
        Route::put('/forum/comments/{comment}', [ForumController::class, 'updateComment'])->whereNumber('comment');
        Route::delete('/forum/comments/{comment}', [ForumController::class, 'destroyComment'])->whereNumber('comment');
        Route::post('/forum/comments/{comment}/reaction', [ForumController::class, 'toggleCommentReaction'])->whereNumber('comment');

        Route::middleware('game.player')->group(function () {
            Route::post('/posts/{slug}/comments', [PostInteractionController::class, 'storeComment']);
            Route::post('/forum/posts', [ForumController::class, 'store']);
            Route::post('/forum/posts/{post}/comments', [ForumController::class, 'storeComment'])->whereNumber('post');
        });
    });

    Route::post('/sepay/webhook', [SePayController::class, 'webhook']);
    Route::get('/sepay/cron', [SePayController::class, 'cron']);
    Route::post('/napgame247/callback', [TopupCardController::class, 'napgame247Callback']);

    Route::middleware('topup.secret')->group(function () {
        Route::post('/topup/credit', [TopupController::class, 'credit']);

        Route::post('/topup/log', [TopupCardController::class, 'create']);
        Route::get('/topup/log', [TopupCardController::class, 'get']);
        Route::put('/topup/log/{transId}', [TopupCardController::class, 'update']);
        Route::get('/topup/log/history/{username}', [TopupCardController::class, 'history']);
    });

    Route::middleware('topup.secret')->prefix('admin')->group(function () {
        Route::get('/stats', [AdminDashboardController::class, 'stats']);
        Route::get('/history', [AdminDashboardController::class, 'history']);
        Route::get('/revenue', [AdminDashboardController::class, 'revenue']);
        Route::get('/topUsers', [AdminDashboardController::class, 'topUsers']);
        Route::get('/monthlyRevenue', [AdminDashboardController::class, 'monthlyRevenue']);

        Route::get('/accounts', [AdminAccountController::class, 'index']);
        Route::get('/accounts/{id}', [AdminAccountController::class, 'show'])->whereNumber('id');
        Route::post('/accounts', [AdminAccountController::class, 'store']);
        Route::put('/accounts/{id}', [AdminAccountController::class, 'update'])->whereNumber('id');
        Route::delete('/accounts/{id}', [AdminAccountController::class, 'destroy'])->whereNumber('id');

        Route::get('/giftcodes', [AdminGiftcodeController::class, 'index']);
        Route::get('/giftcodes/{id}', [AdminGiftcodeController::class, 'show'])->whereNumber('id');
        Route::post('/giftcodes', [AdminGiftcodeController::class, 'store']);
        Route::put('/giftcodes/{id}', [AdminGiftcodeController::class, 'update'])->whereNumber('id');
        Route::delete('/giftcodes/{id}', [AdminGiftcodeController::class, 'destroy'])->whereNumber('id');

        Route::get('/items', [AdminItemController::class, 'index']);
        Route::get('/items/{id}/options', [AdminItemController::class, 'options'])->whereNumber('id');
        Route::put('/items/{id}', [AdminItemController::class, 'update'])->whereNumber('id');

        Route::get('/shops', [AdminShopController::class, 'index']);
        Route::get('/shops/tab/{tabId}', [AdminShopController::class, 'tab'])->whereNumber('tabId');
        Route::put('/shops/tab/{tabId}', [AdminShopController::class, 'updateTab'])->whereNumber('tabId');
    });
});
