<?php

namespace App\Services\Payment;

use App\Models\UserPlanLog;

class PlanLogService
{
    protected $planLogModel;
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        $this->planLogModel = new UserPlanLog();
    }

    public function createPlanLog(array $planLogArr)
    {
        return $this->planLogModel->create($planLogArr);
    }

    public function getSinglePlanLog(string $id)
    {
        return $this->planLogModel->newQuery()->findOrFail($id);
    }
}
