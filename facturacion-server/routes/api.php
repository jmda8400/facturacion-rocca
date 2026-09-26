<?php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\InvoiceController;
use Illuminate\Support\Facades\Route;
Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:billing-login');
    Route::middleware(['billing.token', 'throttle:billing-api'])->group(function () {
        Route::post('/invoices', [InvoiceController::class, 'store']);
        Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('api.invoices.show');
        Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'download'])->name('api.invoices.download');
    });
});
