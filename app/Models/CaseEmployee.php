<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaseEmployee extends Model
{
    use HasFactory;

    protected $table = "case_employees";

    protected $fillable = ['case_id', 'employee_id', 'status'];


    public function case()
    {
        return $this->belongsTo(CaseModel::class, 'case_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
