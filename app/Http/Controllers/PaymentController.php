<?php

namespace App\Http\Controllers;

use App\Services\Payment\PlanLogService;
use App\Services\Payment\PlanTypeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{

    function __construct(protected PlanTypeService $planTypeService, protected PlanLogService $planLogService) {}

    public function pay()
    {
        $user = Auth::user();
        $planTypes = $this->planTypeService->getAllPlanTypes();
        $paymentMethods = config('params.payment_methods');

        return view('payment.payment-form', compact('user', 'planTypes', 'paymentMethods'));
    }

    public function paymentInit(Request $request)
    {
        $plan = $this->planTypeService->getSinglePlanType($request->plan_id);

        $gateway = $plan->gateway;

        $data = ['user_id' => $request->user_id, 'plan_id' => $plan->id, 'amount' => $plan->price, 'gateway' => $gateway, 'start_date' => now(), 'end_date' => now()->addMonths($plan->duration_month), 'status' => 0];

        $log = $this->planLogService->createPlanLog($data);

        switch ($gateway) {
            case 'stripe':
                return redirect()->route('stripe.session', [$log->user_id, $log->id]);
                break;
            case 'stripe':
                return redirect()->route('paypal.session', [$log->user_id, $log->id]);
                break;

            default:
                # code...
                break;
        }
    }
}
