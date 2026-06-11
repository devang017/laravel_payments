<?php

namespace App\Http\Controllers;

use App\Services\Payment\PlanLogService;
use App\Services\Payment\PlanTypeService;
use App\Services\Payment\StripePaymentService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

    public function stripeWebhook(Request $request)
    {
        $endpoint_secret = Config('services.stripe.webhook_key');

        $payload = $request->getContent();

        $event = null;

        try {
            $event = \Stripe\Event::constructFrom(
                json_decode($payload, true)
            );
        } catch (\UnexpectedValueException $e) {
            // Invalid payload
            return \response('', 400);
        }

        if ($endpoint_secret) {

            $sig_header = $request->header('Stripe-Signature');

            try {
                $event = \Stripe\Webhook::constructEvent(
                    $payload,
                    $sig_header,
                    $endpoint_secret
                );
            } catch (\Stripe\Exception\SignatureVerificationException $e) {
                return response('Webhook error while validating signature.', 400);
            }
        }

        // Handle the event
        switch ($event->type) {
            case 'checkout.session.completed':

                $session = $event->data->object;
                $session_id = $session->id;
                $userId = $session->metadata->user_id ?? null;
                $logId = $session->metadata->log_id ?? null;

                $this->paymentService->updateLogManually([
                    'log_id' => $logId,
                    'session_id' => $session_id,
                    'status' => 1,
                    'session' => $session->toArray(),
                    'user_id' => $userId
                ]);

                Log::info('Stripe Request', [
                    'checkout.session.completed' => [
                        'log_id' => $logId,
                        'session_id' => $session_id,
                        'status' => 1,
                        'session' => $session->toArray(),
                        'user_id' => $userId
                    ]
                ]);

                $planLog = $this->planLogService->getSinglePlanLog($logId);

                $this->userService->updateUser($userId, [
                    'plan_status' => (($planLog->status == 1) ? 1 : 0),
                    'plan_start_date' => $planLog->start_date ?? null,
                    'plan_end_date' => $planLog->end_date
                ]);

                break;

            case 'checkout.session.expired':

                $session = $event->data->object;

                $this->paymentService->updateLogManually([
                    'log_id' => $session->metadata->log_id ?? null,
                    'session_id' => $session->id,
                    'status' => 2,
                    'session' => $session->toArray(),
                    'user_id' => $session->metadata->user_id ?? null
                ]);

                $planLog = $this->planLogService->getSinglePlanLog($session->metadata->log_id);

                $this->userService->updateUser($session->metadata->user_id, [
                    'plan_status' => (($planLog->status == 1) ? 1 : 0),
                    'plan_start_date' => $planLog->start_date ?? null,
                    'plan_end_date' => $planLog->end_date
                ]);

                break;

            case 'payment_intent.payment_failed':

                $paymentIntent = $event->data->object;

                $this->paymentService->updateLogManually([
                    'log_id' => $paymentIntent->metadata->log_id ?? null,
                    'session_id' => $paymentIntent->id,
                    'session' => $paymentIntent->toArray(),
                    'status' => 2,
                    'user_id' => $paymentIntent->metadata->user_id ?? null,
                ]);

                $planLog = $this->planLogService->getSinglePlanLog($paymentIntent->metadata->log_id);

                $this->userService->updateUser($paymentIntent->metadata->user_id, [
                    'plan_status' => (($planLog->status == 1) ? 1 : 0),
                    'plan_start_date' => $planLog->start_date ?? null,
                    'plan_end_date' => $planLog->end_date
                ]);

                break;

            default:
        }

        return \response('', 200);
    }
}
