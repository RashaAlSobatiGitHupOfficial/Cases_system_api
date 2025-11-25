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
        return $this->belongsToMany(CaseModel::class, 'case_employees');
    }
}
