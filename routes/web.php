<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\ItemController;
use App\Http\Controllers\Admin\BorrowRequestController;
use App\Http\Controllers\Admin\ReturnRequestController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\User\BorrowItemsController;
use App\Http\Controllers\User\MyBorrowedItemsController;
use App\Http\Controllers\User\ReturnItemsController;
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

        // Return Requests
        Route::get('return-requests', [ReturnRequestController::class, 'index'])->name('return.requests');
        Route::get('return-requests/list', [ReturnRequestController::class, 'list'])->name('return.requests.list');
        Route::post('return-requests/{returnRequest}/process', [ReturnRequestController::class, 'process'])->name('return.requests.process');
        Route::get('return-requests/{returnRequest}', [ReturnRequestController::class, 'show'])->name('return.requests.show');

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

        // Return Items
        Route::get('items/search-property', [ReturnItemsController::class, 'searchByProperty'])->name('user.items.search-property');
        Route::post('items/damage-reports', [ItemDamageReportController::class, 'store'])->name('user.items.damage-reports.store');
        Route::get('return-items', [ReturnItemsController::class, 'index'])->name('return.items');
        Route::get('return-items/list', [ReturnItemsController::class, 'list'])->name('user.return.items.list');

        Route::patch('return-items/{returnRequest}', [ReturnItemsController::class, 'update'])->name('user.return.items.update');

        // Batch request
        Route::post('return-items/request', [ReturnItemsController::class, 'requestReturn'])->name('user.return.items.request');
    });

require __DIR__.'/auth.php';









