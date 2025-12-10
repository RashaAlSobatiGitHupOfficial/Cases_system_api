<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'gender',
        'phone'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function cases()
    {
        return $this->belongsToMany(CaseModel::class, 'case_employees','employee_id', 'case_id')
        ->withPivot(['is_primary', 'action', 'assigned_by', 'started_at', 'ended_at'])
        ->withTimestamps();

    }
public function caseEmployees()
{
    return $this->hasMany(CaseEmployee::class, 'employee_id');
}


}
