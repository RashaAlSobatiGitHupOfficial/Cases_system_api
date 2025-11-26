<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Priority extends Model
{
    use HasFactory;

    protected $fillable = ['priority_name', 'delay_time'];


    public function cases()
    {
        return $this->hasMany(CaseModel::class);
    }
}
