<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaseLog extends Model
{
    use HasFactory;

    protected $table = 'case_logs';

    protected $fillable = [
        'case_id',
        'user_id',
        'action',
        'old_value',
        'new_value',
    ];

    // Relationships
    public function case()
    {
        return $this->belongsTo(CaseModel::class, 'case_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
