<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaseEmployee extends Model
{
    use HasFactory;

    protected $table = "case_employees";

    protected $fillable = ['case_id', 'employee_id', 'is_primary','action','assigned_by','started_at','ended_at'];


    public function case()
    {
        return $this->belongsTo(CaseModel::class, 'case_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
