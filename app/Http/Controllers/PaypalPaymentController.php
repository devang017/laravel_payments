<?php

namespace App\Http\Controllers;

use App\Services\Payment\PlanLogService;
use App\Services\PaypalPaymentService;
use App\Services\UserService;
use Illuminate\Http\Request;

class PaypalPaymentController extends Controller
{
    public function __construct(protected PaypalPaymentService $paypalPaymentService, protected PlanLogService $planLogService, protected UserService $userService) {}

    public function payment(string $userId, string $logId)
    {
        $link = $this->paypalPaymentService->createPayment($userId, $logId);

        if ($link) {
            return redirect()->away($link);
        }

        return redirect()->route('paypal.cancel', [$userId, $logId])->with('error', 'Unable to create PayPal order.');
    }

    public function success(Request $request, string $userId, string $logId)
    {
        $token = $request->get('token');

        if (!$token) {
            return redirect()->route('paypal.cancel', [$userId, $logId])->with('error', 'Invalid PayPal token.');
        }

        $response = $this->paypalPaymentService->handleSuccess($token, $userId, $logId);

        if (isset($response['status']) && $response['status'] === 'COMPLETED') {

            $planLog = $this->planLogService->getSinglePlanLog($userId);

            $this->userService->updateUser($userId, [
                'plan_status' => (($planLog->status == 1) ? 1 : 0),
                'plan_start_date' => $planLog->start_date ?? null,
                'plan_end_date' => $planLog->end_date
            ]);

            return redirect()->route('dashboard')->with('success', 'Payment completed successfully.');
        }

        return redirect()->route('paypal.cancel', [$userId, $logId])->with('error', 'Payment failed.');
    }

    public function cancel(string $userId, string $logId)
    {
        $this->paypalPaymentService->handleCancel($userId, $logId);

        return redirect()->route('dashboard')->with('error', 'Payment was cancelled.');
    }
}
