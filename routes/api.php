<?php

use App\Http\Controllers\Api\MisOrLocationController;
use Illuminate\Support\Facades\Route;

Route::prefix('locations')->name('api.locations.')->group(function () {
    Route::get('/municipalities', [MisOrLocationController::class, 'municipalities'])->name('municipalities');
    Route::get('/barangays/{municipality}', [MisOrLocationController::class, 'barangays'])->name('barangays');
});
