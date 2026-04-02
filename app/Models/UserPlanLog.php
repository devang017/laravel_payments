<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('user_plan_logs')]
#[Fillable(['user_id', 'plan_id', 'amount', 'ref_id', 'gateway', 'logs', 'status', 'start_date', 'end_date'])]
class UserPlanLog extends Model {}
