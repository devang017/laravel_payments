<?php

namespace App\Services;

use App\Models\UserPlanLog;
use Blendbyte\PayPal\Services\PayPal as PayPalClient;

class PaypalPaymentService
{
    protected $planLogModel;

    function __construct()
    {
        $this->planLogModel = new UserPlanLog();
    }

    private function getProvider()
    {
        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));
        $provider->setAccessToken($provider->getAccessToken());

        return $provider;
    }

    /**
     * STEP 1: Create order and redirect user
     */
    public function createPayment(string $userId, string $logId)
    {
        $provider = $this->getProvider();

        $log = $this->planLogModel->findOrFail($logId);

        $response = $provider->createOrder([
            "intent" => 'CAPTURE',
            "purchase_units" => [
                [
                    'amount' => [
                        "currency_code" => config('paypal.currency'),
                        "value" => (string)$log->amount
                    ],
                    'description' => 'Paypal Payment For Subscription',
                ]
            ],
            "application_context" => [
                'cancel_url' => route('paypal.cancel', [$userId, $logId]),
                'return_url' => route('paypal.success', [$userId, $logId]),
            ]
        ]);

        if (isset($response['id']) && $response['status'] === 'CREATED') {
            foreach ($response['links'] as $link) {
                if ($link['rel'] === 'approve') {
                    return $link['href'];
                }
            }
        }

        return false;
    }

    /**
     * STEP 2: Handle success & capture payment
     */
    public function handleSuccess(string $token, string $userId, string $logId)
    {
        $provider = $this->getProvider();
        $response = $provider->capturePaymentOrder($token);

        // Save payment log
        $this->planLogModel->where('id', $logId)->update([
            'ref_id' => $response['id'] ?? null,
            'user_id' => $userId,
            'status' => ($response['status'] ?? '') === 'COMPLETED' ? 1 : 2,
            'logs' => json_encode($response),
        ]);

        return $response;
    }

    /**
     * STEP 3: Handle cancel
     */
    public function handleCancel(string $userId, string $logId)
    {
        // Optional: mark log as cancelled
        $this->planLogModel->where('id', $logId)->update([
            'status' => 2,
        ]);
    }
}
