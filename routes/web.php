<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\ItemController;
use App\Http\Controllers\Admin\BorrowRequestController;
use App\Http\Controllers\Admin\ReturnItemsController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\User\BorrowItemsController;
use App\Http\Controllers\User\MyBorrowedItemsController;
use App\Http\Controllers\User\LocationController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\User\DashboardController as UserDashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Admin\ItemInstanceController;
use App\Http\Controllers\User\ItemDamageReportController;


// ??? Root should redirect to login instead of welcome
Route::get('/', function () {
    return view('auth.login-register');
});


Route::middleware('auth')->group(function () {
    // Profile pages
    Route::get('/profile/info', [ProfileController::class, 'editInfo'])->name('profile.info');
    Route::get('/profile/password', [ProfileController::class, 'editPassword'])->name('profile.password');
    Route::get('/profile/delete', [ProfileController::class, 'editDelete'])->name('profile.delete');

    // Profile actions
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/list', [NotificationController::class, 'list'])->name('notifications.list');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.readAll');
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
        Route::resource('items', ItemController::class);

        Route::post('items/validate-pns', [ItemController::class, 'validatePropertyNumbers'])
            ->name('admin.items.validate-pns');

        Route::patch('item-instances/{instance}', [ItemInstanceController::class, 'update'])
            ->name('admin.item-instances.update');

        Route::delete('item-instances/{instance}', [ItemInstanceController::class, 'destroy'])
            ->name('admin.item-instances.destroy');

            // categories & offices API for admin UI
        Route::get('api/categories', [\App\Http\Controllers\Admin\CategoryController::class, 'index']);
        Route::post('api/categories', [\App\Http\Controllers\Admin\CategoryController::class, 'store']);
        Route::delete('api/categories/{name}', [\App\Http\Controllers\Admin\CategoryController::class, 'destroy']);

        Route::get('api/offices', [\App\Http\Controllers\Admin\OfficeController::class, 'index']);
        Route::post('api/offices', [\App\Http\Controllers\Admin\OfficeController::class, 'store']);
        Route::delete('api/offices/{code}', [\App\Http\Controllers\Admin\OfficeController::class, 'destroy']);
                
        // Borrow Requests
        Route::get('borrow-requests', [BorrowRequestController::class, 'index'])->name('borrow.requests');
        Route::get('borrow-requests/list', [BorrowRequestController::class, 'list'])->name('admin.borrow.requests.list');
        Route::post('borrow-requests/{borrowRequest}/update-status', [BorrowRequestController::class, 'updateStatus'])->name('admin.borrow.requests.update-status');
        // Dispatch action
        Route::post('borrow-requests/{borrowRequest}/dispatch', [BorrowRequestController::class, 'dispatch'])
            ->name('admin.borrow.requests.dispatch');

        // Return Items
        Route::get('return-items', [ReturnItemsController::class, 'index'])->name('admin.return-items.index');
        Route::get('return-items/list', [ReturnItemsController::class, 'list'])->name('admin.return-items.list');
        Route::get('return-items/{borrowRequest}', [ReturnItemsController::class, 'show'])->name('admin.return-items.show');
        Route::post('return-items/{borrowRequest}/collect', [ReturnItemsController::class, 'collect'])->name('admin.return-items.collect');
        Route::patch('return-items/instances/{borrowItemInstance}', [ReturnItemsController::class, 'updateInstance'])->name('admin.return-items.instances.update');

        // Reports
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::post('reports/data', [ReportController::class, 'data'])->name('reports.data');
        Route::get('reports/export/pdf', [ReportController::class, 'exportPdf'])->name('reports.export.pdf');
        Route::get('reports/export/xlsx', [ReportController::class, 'exportXlsx'])->name('reports.export.xlsx');
        });
    

// ========================
// User Routes
// ========================
Route::middleware(['auth', 'role:user', 'nocache'])
    ->prefix('user')
    ->group(function () {

        Route::get('/dashboard', [UserDashboardController::class, 'index'])->name('user.dashboard');
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

        // Confirm delivery (user)
        Route::post('my-borrowed-items/{borrowRequest}/confirm-delivery', [MyBorrowedItemsController::class, 'confirmDelivery'])
            ->name('user.borrowed.items.confirm_delivery');

        // Report not received (user)
        Route::post('my-borrowed-items/{borrowRequest}/report-not-received', [MyBorrowedItemsController::class, 'reportNotReceived'])
            ->name('user.borrowed.items.report_not_received');

    });

require __DIR__.'/auth.php';



