<?php

use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StripePaymentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('payment/pay-form', [PaymentController::class, 'pay'])->name('payment.form');
    Route::post('payment/init-payment', [PaymentController::class, 'paymentInit'])->name('payment.init');
    Route::get('stripe/checkout-session/{user_id}/{log_id}', [StripePaymentController::class, 'session'])->name('stripe.session');
    Route::get('stripe/checkout-success/{user_id}/{log_id}', [StripePaymentController::class, 'success'])->name('stripe.success');
    Route::get('stripe/checkout-cancel/{user_id}/{log_id}', [StripePaymentController::class, 'cancel'])->name('stripe.cancel');
});

require __DIR__ . '/auth.php';
