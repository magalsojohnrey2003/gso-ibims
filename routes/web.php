<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\ItemController;
use App\Http\Controllers\Admin\BorrowRequestController;
use App\Http\Controllers\Admin\ReturnItemsController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\RejectionReasonController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\User\BorrowItemsController;
use App\Http\Controllers\User\MyBorrowedItemsController;
use App\Http\Controllers\User\LocationController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\User\DashboardController as UserDashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Admin\ItemInstanceController;
use App\Http\Controllers\User\ItemDamageReportController;
use App\Http\Controllers\Admin\ManpowerRequestController as AdminManpowerRequestController;
use App\Http\Controllers\User\ManpowerRequestController as UserManpowerRequestController;
use App\Http\Controllers\ManpowerRoleController;
use App\Http\Controllers\PublicManpowerRequestController;
use App\Http\Controllers\WelcomeController;


Route::get('/', [WelcomeController::class, 'landing'])->name('landing');
Route::get('/features/borrow-items', [WelcomeController::class, 'publicBorrowItems'])->name('public.borrow-items');

Route::get('/manpower/status/{token}', [PublicManpowerRequestController::class, 'show'])
    ->name('manpower.requests.public.show');


Route::middleware('auth')->group(function () {
    // Profile pages
    Route::get('/profile', [ProfileController::class, 'editInfo'])->name('profile.show');
    Route::get('/profile/info', [ProfileController::class, 'editInfo'])->name('profile.info');
    Route::get('/profile/password', [ProfileController::class, 'editPassword'])->name('profile.password');
    Route::get('/profile/delete', [ProfileController::class, 'editDelete'])->name('profile.delete');

    // Profile actions
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Legacy / convenience dashboard route used by tests and some auth flows
    Route::get('/dashboard', [UserDashboardController::class, 'index'])->name('dashboard');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/list', [NotificationController::class, 'list'])->name('notifications.list');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.readAll');

    Route::get('/manpower-roles', [ManpowerRoleController::class, 'index'])->name('manpower.roles.index');
});


// ========================
// Admin Routes
// ========================
Route::middleware(['auth', 'role:admin', 'nocache'])
    ->prefix('admin')
    
    ->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

         // === Dashboard API endpoints ===
        // API endpoints used by the admin dashboard JS
        Route::get('/dashboard/borrow-trends', [DashboardController::class, 'borrowTrends']);
        Route::get('/dashboard/most-borrowed', [DashboardController::class, 'mostBorrowed']);

        // Dashboard API endpoints
        Route::get('/dashboard/borrow-trends', [DashboardController::class, 'borrowTrends'])->name('admin.dashboard.borrow-trends');
        Route::get('/dashboard/most-borrowed', [DashboardController::class, 'mostBorrowed'])->name('admin.dashboard.most-borrowed');

        // Item Management
        Route::get('items/search', [ItemController::class, 'search'])->name('admin.items.search');
        Route::get('items/check-serial', [ItemController::class, 'checkSerial'])->name('admin.items.check-serial');
        Route::match(['get', 'post'], 'items/{item}/stickers', [ItemController::class, 'printStickers'])->name('admin.items.stickers');
        Route::get('items/instances/{instance}/history', [ItemController::class, 'instanceHistory'])->name('admin.items.instances.history');
        Route::resource('items', ItemController::class);

        Route::post('items/validate-pns', [ItemController::class, 'validatePropertyNumbers'])
            ->name('admin.items.validate-pns');

        Route::patch('item-instances/{instance}', [ItemInstanceController::class, 'update'])
            ->name('admin.item-instances.update');

        Route::patch('item-instances/{instance}/condition', [ItemInstanceController::class, 'updateCondition'])
            ->name('admin.item-instances.update-condition');

        Route::delete('item-instances/{instance}', [ItemInstanceController::class, 'destroy'])
            ->name('admin.item-instances.destroy');

            // categories & offices API for admin UI
        Route::get('api/categories', [\App\Http\Controllers\Admin\CategoryController::class, 'index']);
        Route::post('api/categories', [\App\Http\Controllers\Admin\CategoryController::class, 'store']);
        Route::delete('api/categories/{name}', [\App\Http\Controllers\Admin\CategoryController::class, 'destroy']);
        
        // GLA sub-categories management
        Route::get('api/categories/{category}/glas', [\App\Http\Controllers\Admin\CategoryController::class, 'getGLAs']);
        Route::post('api/categories/{category}/glas', [\App\Http\Controllers\Admin\CategoryController::class, 'storeGLA']);
        Route::delete('api/categories/{category}/glas/{gla}', [\App\Http\Controllers\Admin\CategoryController::class, 'destroyGLA']);

        Route::get('api/offices', [\App\Http\Controllers\Admin\OfficeController::class, 'index']);
        Route::post('api/offices', [\App\Http\Controllers\Admin\OfficeController::class, 'store']);
        Route::delete('api/offices/{code}', [\App\Http\Controllers\Admin\OfficeController::class, 'destroy']);
                
        // Borrow Requests
        Route::get('borrow-requests', [BorrowRequestController::class, 'index'])->name('borrow.requests');
        Route::get('borrow-requests/list', [BorrowRequestController::class, 'list'])->name('admin.borrow.requests.list');
        Route::post('borrow-requests/{borrowRequest}/update-status', [BorrowRequestController::class, 'updateStatus'])->name('admin.borrow.requests.update-status');
        Route::get('borrow-requests/{borrowRequest}/scan', [BorrowRequestController::class, 'scan'])
            ->middleware('signed')
            ->name('admin.borrow.requests.scan');
        // Dispatch action
        Route::post('borrow-requests/{borrowRequest}/dispatch', [BorrowRequestController::class, 'dispatch'])
            ->name('admin.borrow.requests.dispatch');
        Route::match(['get', 'post'], 'borrow-requests/{borrowRequest}/stickers', [BorrowRequestController::class, 'printStickers'])
            ->name('admin.borrow.requests.stickers');

        // Rejection Reasons
        Route::prefix('rejection-reasons')->name('admin.rejection-reasons.')->group(function () {
            Route::get('/', [RejectionReasonController::class, 'index'])->name('index');
            Route::post('/', [RejectionReasonController::class, 'store'])->name('store');
            Route::get('{rejectionReason}', [RejectionReasonController::class, 'show'])->name('show');
            Route::delete('{rejectionReason}', [RejectionReasonController::class, 'destroy'])->name('destroy');
        });

        // User Management (admin)
        // Provides listing, creating, updating (including password/email changes), and deleting users.
        Route::post('users/{user}/restore', [UserController::class, 'restore'])->name('admin.users.restore');
        Route::delete('users/{user}/force-destroy', [UserController::class, 'forceDestroy'])->name('admin.users.force-destroy');
        Route::get('users/{user}/damage-history', [UserController::class, 'damageHistory'])->name('admin.users.damage-history');
        Route::resource('users', UserController::class)->names('admin.users');

        // Return Items
        Route::get('return-items', [ReturnItemsController::class, 'index'])->name('admin.return-items.index');
        Route::get('return-items/list', [ReturnItemsController::class, 'list'])->name('admin.return-items.list');
        Route::get('return-items/walk-in/{id}', [ReturnItemsController::class, 'showWalkIn'])->name('admin.return-items.show-walkin');
        Route::post('return-items/walk-in/{id}/collect', [ReturnItemsController::class, 'collectWalkIn'])->name('admin.return-items.collect-walkin');
        Route::get('return-items/{borrowRequest}', [ReturnItemsController::class, 'show'])->name('admin.return-items.show');
        Route::post('return-items/{borrowRequest}/collect', [ReturnItemsController::class, 'collect'])->name('admin.return-items.collect');
        Route::patch('return-items/instances/{borrowItemInstance}', [ReturnItemsController::class, 'updateInstance'])->name('admin.return-items.instances.update');

        // Reports
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::post('reports/data', [ReportController::class, 'data'])->name('reports.data');
        Route::get('reports/export/pdf', [ReportController::class, 'exportPdf'])->name('reports.export.pdf');
        Route::get('reports/export/xlsx', [ReportController::class, 'exportXlsx'])->name('reports.export.xlsx');

    // Walk-in Requests
    Route::get('walk-in', [BorrowRequestController::class, 'walkInIndex'])->name('admin.walkin.index');
    Route::get('walk-in/create', [BorrowRequestController::class, 'walkInCreate'])->name('admin.walkin.create');
    Route::get('walk-in/list', [BorrowRequestController::class, 'walkInList'])->name('admin.walkin.list');
    Route::get('walk-in/print/{walkInRequest}', [BorrowRequestController::class, 'walkInPrint'])->name('admin.walkin.print');
    Route::post('walk-in/store', [BorrowRequestController::class, 'walkInStore'])->name('admin.walkin.store');
    Route::get('walk-in/approve-qr/{id}', [BorrowRequestController::class, 'walkInApproveQr'])->name('admin.walkin.approve.qr');
    Route::post('walk-in/deliver/{id}', [BorrowRequestController::class, 'walkInDeliver'])->name('admin.walkin.deliver');

        Route::get('/manpower-roles', [ManpowerRoleController::class, 'index'])->name('admin.manpower.roles.index');
        Route::post('/manpower-roles', [ManpowerRoleController::class, 'store'])->name('admin.manpower.roles.store');
        Route::delete('/manpower-roles/{manpowerRole}', [ManpowerRoleController::class, 'destroy'])->name('admin.manpower.roles.destroy');

        // Manpower Requests (Admin)
        Route::prefix('manpower-requests')->name('admin.manpower.requests.')->group(function() {
            Route::get('/', [AdminManpowerRequestController::class, 'index'])->name('index');
            Route::get('/list', [AdminManpowerRequestController::class, 'list'])->name('list');
            Route::get('/{manpowerRequest}/scan', [AdminManpowerRequestController::class, 'scan'])
                ->middleware('signed')
                ->name('scan');
            Route::post('/{manpowerRequest}/status', [AdminManpowerRequestController::class, 'updateStatus'])->name('status');
        });
        });
    

// ========================
// User Routes
// ========================
Route::middleware(['auth', 'role:user', 'nocache'])
    ->prefix('user')
    ->group(function () {

        Route::get('/dashboard', [UserDashboardController::class, 'index'])->name('user.dashboard');
        Route::get('/terms', [UserDashboardController::class, 'showTerms'])->name('user.terms');
        Route::post('/accept-terms', [UserDashboardController::class, 'acceptTerms'])->name('user.terms.accept');
         // API endpoints
    Route::get('/dashboard/my-requests', [UserDashboardController::class, 'myRequests']);
    Route::post('/dashboard/requests/{id}/cancel', [UserDashboardController::class, 'cancelRequest']);
    Route::post('/dashboard/requests/{id}/request-return', [UserDashboardController::class, 'requestReturn']);
    Route::get('/dashboard/borrow-trends', [UserDashboardController::class, 'borrowTrends']);
    Route::get('/dashboard/available-items', [UserDashboardController::class, 'availableItems']);

        // Borrow Items
        Route::get('borrow-items', [BorrowItemsController::class, 'index'])->name('borrow.items');

        // Cart actions
        Route::get('borrow-list', [BorrowItemsController::class, 'borrowList'])->name('borrowList.index');
        Route::post('borrow-list/add/{item}', [BorrowItemsController::class, 'addToBorrowList'])->name('borrowList.add');
        Route::delete('borrow-list/remove/{item}', [BorrowItemsController::class, 'removeFromBorrowList'])->name('borrowList.remove');
        Route::post('borrow-list/submit', [BorrowItemsController::class, 'submitRequest'])->name('borrowList.submit');

        // Availability
        Route::get('availability/{item}', [BorrowItemsController::class, 'availability'])->name('borrowList.availability');
        Route::get('availability', [BorrowItemsController::class, 'availabilityMultiple'])->name('borrowList.availability.multiple');
        // Location lookup
        Route::get('locations/barangays', [LocationController::class, 'barangays'])->name('user.locations.barangays');
        Route::get('locations/puroks', [LocationController::class, 'puroks'])->name('user.locations.puroks');

        // My Borrowed Items
        Route::get('my-borrowed-items', [MyBorrowedItemsController::class, 'index'])->name('my.borrowed.items');
        Route::get('my-borrowed-items/list', [MyBorrowedItemsController::class, 'list'])->name('user.borrowed.items.list');
        Route::get('my-borrowed-items/{borrowRequest}', [MyBorrowedItemsController::class, 'show'])->name('user.borrowed.items.show');

        // Print the borrow request (GET)
        Route::get('my-borrowed-items/{borrowRequest}/print', [MyBorrowedItemsController::class, 'print'])
            ->name('user.borrowed.items.print');

        Route::get('my-borrowed-items/{borrowRequest}/routing-slip', [MyBorrowedItemsController::class, 'routingSlip'])
            ->name('user.borrowed.items.routing_slip');

        // Confirm delivery (user)
        Route::post('my-borrowed-items/{borrowRequest}/confirm-delivery', [MyBorrowedItemsController::class, 'confirmDelivery'])
            ->name('user.borrowed.items.confirm_delivery');

        // Report not received (user)
        Route::post('my-borrowed-items/{borrowRequest}/report-not-received', [MyBorrowedItemsController::class, 'reportNotReceived'])
            ->name('user.borrowed.items.report_not_received');

        Route::post('my-borrowed-items/{borrowRequest}/mark-returned', [MyBorrowedItemsController::class, 'markReturned'])
            ->name('user.borrowed.items.mark_returned');

        // Manpower (User)
        Route::prefix('manpower')->name('user.manpower.')->group(function() {
            Route::get('/', [UserManpowerRequestController::class, 'index'])->name('index');
            Route::get('/list', [UserManpowerRequestController::class, 'list'])->name('list');
            Route::post('/store', [UserManpowerRequestController::class, 'store'])->name('store');
            Route::get('/print/{id}', [UserManpowerRequestController::class, 'print'])->name('print');
        });

    });

require __DIR__.'/auth.php';
