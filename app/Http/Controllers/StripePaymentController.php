<?php

namespace App\Http\Controllers;

use App\Interfaces\Payment\PaymentGatewayInterface;
use App\Services\Payment\PlanLogService;
use App\Services\Payment\PlanTypeService;
use Illuminate\Http\Request;

class StripePaymentController extends Controller
{
    public function __construct(protected PlanTypeService $planTypeService, protected PlanLogService $planLogService, protected PaymentGatewayInterface $paymentService) {}

    public function pay()
    {
        $user = auth()->user();
        $planTypes = $this->planTypeService->getAllPlanTypes();
        $paymentMethods = config('params.payment_methods');

        return view('payment.payment-form', compact('user', 'planTypes', 'paymentMethods'));
    }

    public function paymentInit(Request $request)
    {
        $plan = $this->planTypeService->getSinglePlanType($request->plan_id);

        $data = [
            'user_id' => $request->user_id,
            'plan_id' => $plan->id,
            'amount' => $plan->price,
            'start_date' => now(),
            'end_date' => now()->addMonths($plan->duration_month),
            'status' => 0,
        ];

        $log = $this->planLogService->createPlanLog($data);

        return redirect()->route('stripe.session', [$log->user_id, $log->id]);
    }

    public function session(string $userId, string $logId)
    {
        $log = $this->planLogService->getSinglePlanLog($logId);

        $url = $this->paymentService->createSession($log, $userId);

        return redirect($url);
    }

    public function success(Request $request, string $userId, string $logId)
    {
        if (!$request->session_id) {
            throw new \Exception('Session Id Missing');
        }

        $result = $this->paymentService->handleSuccess($request->session_id, $logId, $userId);

        return redirect()->route('dashboard')->with($result['status'], $result['message']);
    }

    public function cancel(Request $request, string $userId, string $logId)
    {
        $this->paymentService->handleCancel($request->session_id, $logId, $userId);

        return redirect()->route('dashboard')->with('error', 'Payment Was Cancelled');
    }
}
