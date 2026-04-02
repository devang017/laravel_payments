<?php

namespace App\Http\Controllers;

use App\Models\PlanType;
use App\Models\UserPlanLog;
use Exception;
use Illuminate\Http\Request;
use Stripe\checkout\Session;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class StripePaymentController extends Controller
{
    public function pay()
    {
        $user = auth()->user();
        $planTypes = PlanType::all();
        $paymentMethods = Config('params.payment_methods');

        return view('payment.payment-form', compact('user', 'planTypes', 'paymentMethods'));
    }

    public function paymentInit(Request $request)
    {
        $getMonths = PlanType::findOrFail($request->plan_id);
        $data = $request->all() + ['start_date' => now(), 'end_date' => now()->addMonths($getMonths->duration_month)];
        $log = UserPlanLog::create($data);
        return redirect()->route('stripe.session', [$log->user_id, $log->id]);
    }

    /**
     * session function
     *
     * @param Request $request
     * @param string $userId
     * @param string $logId
     * @return void
     */
    public function session(string $userId, string $logId)
    {
        Stripe::setApiKey(config('services.stripe.secret_key'));

        $logid = UserPlanLog::findOrFail($logId);

        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => 'subscription'
                    ],
                    'unit_amount' => (int)round($logid->amount * 100)
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => route('stripe.success', [$userId, $logId]) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('stripe.cancel', [$userId, $logId]) . '?session_id={CHECKOUT_SESSION_ID}',
        ]);

        return redirect($session->url);
    }

    /**
     * success function
     *
     * @param Request $request
     * @param string $userId
     * @param string $logId
     * @return void
     */
    public function success(Request $request, string $userId, string $logId)
    {
        Stripe::setApiKey(config('services.stripe.secret_key'));

        $sessionId = $request->session_id;

        if (!$sessionId) {
            throw new Exception('Session Id Missing');
        }

        $session = Session::retrieve($sessionId);
        $payment = PaymentIntent::retrieve($session->payment_intent);

        UserPlanLog::where('id', $logId)->update([
            'ref_id' => $payment->id,
            'user_id' => $userId,
            'status' => 1,
            'logs' => json_encode($payment->toArray())
        ]);

        $status = $payment->status === 'succeeded' ? 'success' : 'error';

        $message = $status === 'success' ? 'Payment Completed Successfully' : 'Payment Was Cancelled';

        return redirect()->route('dashboard')->with($status, $message);
    }

    /**
     * cancel function
     *
     * @param Request $request
     * @param string $userId
     * @param string $logId
     * @return void
     */
    public function cancel(Request $request, string $userId, string $logId)
    {
        Stripe::setApiKey(config('services.stripe.secret_key'));
        $sessionId = $request->session_id;
        $session = Session::retrieve($sessionId);

        UserPlanLog::where('id', $logId)->update([
            'ref_id' => $session->id,
            'user_id' => $userId,
            'status' => 1,
            'logs' => json_encode($session->toArray())
        ]);

        return redirect()->route('dashboard')->with('error', 'Payment Was Cancelled');
    }
}
