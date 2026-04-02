<?php

namespace App\Providers;

use App\Interfaces\Payment\PaymentGatewayInterface;
use App\Services\Payment\StripePaymentService;
use Illuminate\Support\ServiceProvider;
// future:
// use App\Services\Payment\RazorpayService;

class PaymentServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register()
    {
        $this->app->bind(PaymentGatewayInterface::class, function ($app) {
            $gateway = request()->input('gateway', 'Stripe');

            return match ($gateway) {
                'Stripe' => $app->make(StripePaymentService::class),
                // 'razorpay' => $app->make(RazorpayService::class),
                default => throw new \Exception('Invalid Payment Gateway'),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
