<?php

namespace App\Http\Controllers;

use App\Services\Payment\PlanLogService;
use App\Services\Payment\PlanTypeService;
use App\Services\Payment\StripePaymentService;
use App\Services\UserService;
use Illuminate\Http\Request;

class StripePaymentController extends Controller
{
    public function __construct(protected PlanTypeService $planTypeService, protected PlanLogService $planLogService, protected StripePaymentService $paymentService, protected UserService $userService) {}

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

        if ($result['status'] == 'success') {

            $planLog = $this->planLogService->getSinglePlanLog($userId);

            $this->userService->updateUser($userId, [
                'plan_status' => (($planLog->status == 1) ? 1 : 0),
                'plan_start_date' => $planLog->start_date ?? null,
                'plan_end_date' => $planLog->end_date
            ]);
        }

        return redirect()->route('dashboard')->with($result['status'], $result['message']);
    }

    public function cancel(Request $request, string $userId, string $logId)
    {
        $this->paymentService->handleCancel($request->session_id, $logId, $userId);

        return redirect()->route('dashboard')->with('error', 'Payment Was Cancelled');
    }
}
