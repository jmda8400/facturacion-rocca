<?php

use App\Http\Controllers\InvoiceController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['billing.api', 'throttle:60,1'])->group(function () {
    Route::post('/invoices', [InvoiceController::class, 'store']);
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('api.invoices.show');
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'download'])->name('api.invoices.download');
});
