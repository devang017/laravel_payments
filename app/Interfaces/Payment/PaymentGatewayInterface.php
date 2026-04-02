<?php

namespace App\Interfaces\Payment;

use App\Models\UserPlanLog;

interface PaymentGatewayInterface
{
    public function createSession(UserPlanLog $log, string $userId): string;

    public function handleSuccess(string $sessionId, string $logId, string $userId): array;

    public function handleCancel(string $sessionId, string $logId, string $userId): void;
}
