<?php

namespace App\Services\Payment;

use App\Models\PlanType;

class PlanTypeService
{
    /**
     * Create a new class instance.
     */
    public function __construct(protected PlanType $planTypeModel) {}

    public function getAllPlanTypes()
    {
        return $this->planTypeModel->newQuery()->all();
    }

    public function getSinglePlanType(string $id)
    {
        return $this->planTypeModel->newQuery()->firstOrFail($id);
    }
}
