<?php

namespace App\Services\Payment;

use App\Interfaces\Payment\PaymentGatewayInterface;
use App\Models\UserPlanLog;
use Stripe\Checkout\Session;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class StripePaymentService implements PaymentGatewayInterface
{
    public function __construct(protected UserPlanLog $planLogModel)
    {
        Stripe::setApiKey(config('services.stripe.secret_key'));
    }

    public function createSession(UserPlanLog $log, string $userId): string
    {
        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => 'Subscription',
                    ],
                    'unit_amount' => (int) round($log->amount * 100),
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => route('stripe.success', [$userId, $log->id]) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('stripe.cancel', [$userId, $log->id]) . '?session_id={CHECKOUT_SESSION_ID}',
        ]);

        return $session->url;
    }

    public function handleSuccess(string $sessionId, string $logId, string $userId): array
    {
        $session = Session::retrieve($sessionId);
        $payment = PaymentIntent::retrieve($session->payment_intent);

        $this->planLogModel->newQuery()
            ->where('id', $logId)
            ->update([
                'ref_id' => $payment->id,
                'user_id' => $userId,
                'status' => $payment->status === 'succeeded' ? 1 : 0,
                'logs' => json_encode($payment->toArray()),
            ]);

        return [
            'status' => $payment->status === 'succeeded' ? 'success' : 'error',
            'message' => $payment->status === 'succeeded'
                ? 'Payment Completed Successfully'
                : 'Payment Failed',
        ];
    }

    public function handleCancel(string $sessionId, string $logId, string $userId): void
    {
        $session = Session::retrieve($sessionId);

        $this->planLogModel->newQuery()
            ->where('id', $logId)
            ->update([
                'ref_id' => $session->id,
                'user_id' => $userId,
                'status' => 0,
                'logs' => json_encode($session->toArray()),
            ]);
    }
}
