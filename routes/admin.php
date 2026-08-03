<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Api\Admin\AccountController;
use App\Http\Controllers\Api\Admin\AdminLogController;
use App\Http\Controllers\Api\Admin\BackAccessoryController;
use App\Http\Controllers\Api\Admin\BadgeController;
use App\Http\Controllers\Api\Admin\CatalogLookupController;
use App\Http\Controllers\Api\Admin\CostumeController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\FlyingBoardController;
use App\Http\Controllers\Api\Admin\ForumController;
use App\Http\Controllers\Api\Admin\GiftBoxController;
use App\Http\Controllers\Api\Admin\GiftcodeController;
use App\Http\Controllers\Api\Admin\HiddenOptionGroupController;
use App\Http\Controllers\Api\Admin\ItemController;
use App\Http\Controllers\Api\Admin\MilestoneController;
use App\Http\Controllers\Api\Admin\NpcTemplateController;
use App\Http\Controllers\Api\Admin\PetController;
use App\Http\Controllers\Api\Admin\PlayerController;
use App\Http\Controllers\Api\Admin\PostController;
use App\Http\Controllers\Api\Admin\ShopController;
use App\Http\Controllers\Api\Admin\TitleItemController;
use App\Http\Controllers\Api\Admin\WelfareConfigController;
use App\Http\Controllers\Api\AdminRuntimeController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function () {
    Route::middleware('throttle:admin-login')->group(function () {
        Route::post('/api/login', [AdminAuthController::class, 'apiLogin']);
    });

    Route::middleware('admin.auth')->group(function () {
        Route::get('/api/me', [AdminAuthController::class, 'me']);
        Route::post('/api/logout', [AdminAuthController::class, 'apiLogout']);

        Route::get('/api/dashboard/stats', [DashboardController::class, 'stats']);
        Route::get('/api/dashboard/history', [DashboardController::class, 'history']);
        Route::get('/api/dashboard/topUsers', [DashboardController::class, 'topUsers']);
        Route::get('/api/dashboard/monthRevenue', [DashboardController::class, 'monthlyRevenue']);
        Route::get('/api/dashboard/overview', [DashboardController::class, 'overview']);

        Route::get('/api/accounts/{id}/player-full', [AccountController::class, 'playerFull'])->whereNumber('id');
        Route::get('/api/accounts/{id}/player-sections/{section}', [AccountController::class, 'playerSection'])->whereNumber('id');
        Route::get('/api/accounts/{id}/activity', [AccountController::class, 'activity'])->whereNumber('id');
        Route::put('/api/accounts/{id}/badges', [AccountController::class, 'updateBadges'])->whereNumber('id');
        Route::apiResource('api/accounts', AccountController::class)
            ->parameters(['accounts' => 'id'])
            ->whereNumber('id')
            ->names('admin.api.accounts');

        Route::get('/api/players/inventory/search', [PlayerController::class, 'inventorySearch']);
        Route::apiResource('api/players', PlayerController::class)
            ->only(['index', 'show'])
            ->parameters(['players' => 'id'])
            ->whereNumber('id')
            ->names('admin.api.players');
        Route::put('/api/players/{id}/stats', [PlayerController::class, 'updateStats'])->whereNumber('id');
        Route::post('/api/players/{id}/inventory/buff', [PlayerController::class, 'buffInventory'])->whereNumber('id');
        Route::post('/api/players/{id}/inventory/revoke', [PlayerController::class, 'revokeInventory'])->whereNumber('id');

        Route::get('/api/giftcodes/{id}/activity', [GiftcodeController::class, 'activity'])->whereNumber('id');
        Route::post('/api/giftcodes/{id}/clone', [GiftcodeController::class, 'clone'])->whereNumber('id');
        Route::apiResource('api/giftcodes', GiftcodeController::class)
            ->parameters(['giftcodes' => 'id'])
            ->whereNumber('id')
            ->names('admin.api.giftcodes');

        Route::get('/api/posts/categories', [PostController::class, 'categories']);
        Route::get('/api/posts/{id}/comments', [PostController::class, 'comments'])->whereNumber('id');
        Route::put('/api/posts/{postId}/comments/{commentId}', [PostController::class, 'updateComment'])->whereNumber('postId')->whereNumber('commentId');
        Route::delete('/api/posts/{postId}/comments/{commentId}', [PostController::class, 'destroyComment'])->whereNumber('postId')->whereNumber('commentId');
        Route::apiResource('api/posts', PostController::class)
            ->parameters(['posts' => 'id'])
            ->whereNumber('id')
            ->names('admin.api.posts');

        Route::get('/api/forum/posts/{id}/comments', [ForumController::class, 'comments'])->whereNumber('id');
        Route::delete('/api/forum/posts/{postId}/comments/{commentId}', [ForumController::class, 'destroyComment'])->whereNumber('postId')->whereNumber('commentId');
        Route::apiResource('api/forum/posts', ForumController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->parameters(['posts' => 'id'])
            ->whereNumber('id')
            ->names('admin.api.forum.posts');

        Route::get('/api/admin-logs', [AdminLogController::class, 'index']);

        Route::post('/api/runtime/shop/reload', [AdminRuntimeController::class, 'reloadShop']);
        Route::get('/api/runtime/health', [AdminRuntimeController::class, 'health']);
        Route::get('/api/runtime/bosses', [AdminRuntimeController::class, 'bosses']);
        Route::post('/api/runtime/bosses', [AdminRuntimeController::class, 'createBoss']);
        Route::post('/api/runtime/bosses/action', [AdminRuntimeController::class, 'bossAction']);
        Route::put('/api/runtime/bosses', [AdminRuntimeController::class, 'updateBoss']);
        Route::get('/api/runtime/boss-configs', [AdminRuntimeController::class, 'bossConfigs']);
        Route::post('/api/runtime/boss-configs', [AdminRuntimeController::class, 'saveBossConfig']);
        Route::get('/api/runtime/map-mobs', [AdminRuntimeController::class, 'mapMobs']);
        Route::post('/api/runtime/map-mobs', [AdminRuntimeController::class, 'saveMapMobs']);
        Route::post('/api/runtime/buffs/mail', [AdminRuntimeController::class, 'buffMail']);
        Route::post('/api/runtime/buffs/account', [AdminRuntimeController::class, 'buffAccount']);

        Route::get('/api/milestones/{type}', [MilestoneController::class, 'index']);
        Route::get('/api/milestones/{type}/{id}', [MilestoneController::class, 'show'])->whereNumber('id');
        Route::post('/api/milestones/{type}', [MilestoneController::class, 'store']);
        Route::post('/api/milestones/{type}/{id}/copy', [MilestoneController::class, 'copy'])->whereNumber('id');
        Route::put('/api/milestones/{type}/{id}', [MilestoneController::class, 'update'])->whereNumber('id');
        Route::delete('/api/milestones/{type}/{id}', [MilestoneController::class, 'destroy'])->whereNumber('id');

        Route::patch('/api/welfare-configs/{id}/toggle', [WelfareConfigController::class, 'toggle'])->whereNumber('id');
        Route::post('/api/welfare-configs/{id}/copy', [WelfareConfigController::class, 'copy'])->whereNumber('id');
        Route::apiResource('api/welfare-configs', WelfareConfigController::class)
            ->parameters(['welfare-configs' => 'id'])
            ->whereNumber('id')
            ->names('admin.api.welfare-configs');

        Route::get('/api/items', [ItemController::class, 'index']);
        Route::get('/api/items/batch', [ItemController::class, 'batch']);
        Route::put('/api/items/{id}', [ItemController::class, 'update'])->whereNumber('id');
        Route::post('/api/items/{id}', [ItemController::class, 'update'])->whereNumber('id');
        Route::get('/api/items/search', [CatalogLookupController::class, 'searchItems']);
        Route::get('/api/options', [CatalogLookupController::class, 'options']);

        Route::get('/api/npcs', [NpcTemplateController::class, 'index']);
        Route::get('/api/npcs/{id}', [NpcTemplateController::class, 'show'])->whereNumber('id');
        Route::post('/api/npcs/{id}', [NpcTemplateController::class, 'update'])->whereNumber('id');

        Route::get('/api/gift-boxes', [GiftBoxController::class, 'index']);
        Route::post('/api/gift-boxes', [GiftBoxController::class, 'store']);
        Route::get('/api/gift-boxes/{id}', [GiftBoxController::class, 'show'])->whereNumber('id');
        Route::post('/api/gift-boxes/{id}', [GiftBoxController::class, 'update'])->whereNumber('id');
        Route::delete('/api/gift-boxes/{id}', [GiftBoxController::class, 'destroy'])->whereNumber('id');

        Route::apiResource('api/hidden-option-groups', HiddenOptionGroupController::class)
            ->parameters(['hidden-option-groups' => 'id'])
            ->whereNumber('id')
            ->names('admin.api.hidden-option-groups');
        Route::post('/api/hidden-option-groups/{id}/copy', [HiddenOptionGroupController::class, 'copy'])
            ->whereNumber('id');

        Route::get('/api/badges', [BadgeController::class, 'index']);
        Route::get('/api/badges/{id}', [BadgeController::class, 'show'])->whereNumber('id');
        Route::post('/api/badges', [BadgeController::class, 'store']);
        Route::post('/api/badges/{id}', [BadgeController::class, 'update'])->whereNumber('id');
        Route::delete('/api/badges/{id}', [BadgeController::class, 'destroy'])->whereNumber('id');

        Route::get('/api/title-items', [TitleItemController::class, 'index']);
        Route::get('/api/title-items/icon/{iconId}', [TitleItemController::class, 'icon'])->whereNumber('iconId');
        Route::post('/api/title-items', [TitleItemController::class, 'store']);
        Route::post('/api/title-items/{id}', [TitleItemController::class, 'update'])->whereNumber('id');

        Route::get('/api/costumes', [CostumeController::class, 'index']);
        Route::post('/api/costumes', [CostumeController::class, 'store']);
        Route::get('/api/costumes/{id}', [CostumeController::class, 'show'])->whereNumber('id');
        Route::post('/api/costumes/{id}', [CostumeController::class, 'update'])->whereNumber('id');
        Route::delete('/api/costumes/{id}', [CostumeController::class, 'destroy'])->whereNumber('id');

        Route::get('/api/pets', [PetController::class, 'index']);
        Route::post('/api/pets', [PetController::class, 'store']);
        Route::get('/api/pets/{id}', [PetController::class, 'show'])->whereNumber('id');
        Route::post('/api/pets/{id}', [PetController::class, 'update'])->whereNumber('id');
        Route::delete('/api/pets/{id}', [PetController::class, 'destroy'])->whereNumber('id');

        Route::get('/api/back-accessories', [BackAccessoryController::class, 'index']);
        Route::post('/api/back-accessories', [BackAccessoryController::class, 'store']);
        Route::get('/api/back-accessories/{id}', [BackAccessoryController::class, 'show'])->whereNumber('id');
        Route::post('/api/back-accessories/{id}', [BackAccessoryController::class, 'update'])->whereNumber('id');
        Route::delete('/api/back-accessories/{id}', [BackAccessoryController::class, 'destroy'])->whereNumber('id');

        Route::get('/api/flying-boards', [FlyingBoardController::class, 'index']);
        Route::post('/api/flying-boards', [FlyingBoardController::class, 'store']);
        Route::get('/api/flying-boards/{id}', [FlyingBoardController::class, 'show'])->whereNumber('id');
        Route::post('/api/flying-boards/{id}', [FlyingBoardController::class, 'update'])->whereNumber('id');
        Route::delete('/api/flying-boards/{id}', [FlyingBoardController::class, 'destroy'])->whereNumber('id');

        Route::get('/api/shops', [ShopController::class, 'index']);
        Route::get('/api/shops/tab/{tabId}', [ShopController::class, 'tab'])->whereNumber('tabId');
        Route::put('/api/shops/tab/{tabId}', [ShopController::class, 'updateTab'])->whereNumber('tabId');
    });

    Route::get('/login', function () {
        return response()
            ->view('admin.spa')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    })->name('admin.login');

    Route::get('/{any?}', function () {
        return response()
            ->view('admin.spa')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    })->where('any', '^(?!api(?:/|$)).*')->middleware('admin.auth');
});
