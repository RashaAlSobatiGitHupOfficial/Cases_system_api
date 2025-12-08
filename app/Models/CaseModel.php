<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaseModel extends Model
{
    use HasFactory;

    protected $table = 'cases';
    protected $appends = ['attachment_url', 'is_mine','allowed'];


    protected $fillable = [
        'client_id',
        'title',
        'description',
        'attachment',
        'note',
        'type',
        'way_entry',
        'status',
        'priority_id',
        'user_id'
    ];

    // Ensure default values if not passed
    protected $attributes = [
        'type' => 'enquery',
        'way_entry' => 'email',
        'status' => 'opened',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }


    public function getAttachmentUrlAttribute()
    {
        return $this->attachment
            ? asset('storage/' . $this->attachment)
            : null;
    }



    public function allEmployees()
    {
        return $this->belongsToMany(Employee::class, 'case_employees', 'case_id', 'employee_id')
            ->withPivot(['is_primary', 'action', 'assigned_by', 'started_at', 'ended_at'])
            ->wherePivotNull('ended_at')
            ->withTimestamps();

    }
    // ACTIVE employees only
    public function employees()
    {
        return $this->allEmployees()->wherePivot('ended_at', null);
    }

    public function getActiveEmployeesListAttribute()
    {
         return $this->employees()->get();
    }



    public function priority()
    {
        return $this->belongsTo(Priority::class);
    }
    public function getIsMineAttribute()
    {
        $user = auth()->user();

        if (!$user || !$user->employee) {
            return false;
        }

        return $this->employees()
            ->where('employee_id', $user->employee->id)
            ->exists();
    }
    public function employeeAssignments()
    {
        return $this->hasMany(CaseEmployee::class, 'case_id');
    }

// public function activeEmployees()
// {
//     return $this->belongsToMany(Employee::class, 'case_employees', 'case_id', 'employee_id')
//         ->withPivot(['is_primary', 'action', 'assigned_by', 'started_at', 'ended_at'])
//         ->wherePivotNull('ended_at')
//         ->withTimestamps();
// }
// public function getActiveEmployeesListAttribute()
// {
//     return $this->activeEmployees()->get();
// }

public function getAllowedAttribute()
{
    $user = auth()->user();
    if (!$user) return [];

    return app(\App\Services\CaseActionService::class)
                ->getAllowedActions($this, $user);
}

}
