<?php

namespace App\Services\Payment;

use App\Models\PlanType;

class PlanTypeService
{
    protected $planTypeModel;
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        $this->planTypeModel = new PlanType();
    }

    public function getAllPlanTypes()
    {
        return $this->planTypeModel->newQuery()->get();
    }

    public function getSinglePlanType(string $id)
    {
        return $this->planTypeModel->newQuery()->findOrFail($id);
    }
}
