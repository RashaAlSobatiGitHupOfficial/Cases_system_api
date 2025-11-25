<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaseModel extends Model
{
    use HasFactory;

    protected $table = 'cases';

    protected $fillable = [
        'client_id',
        'title',
        'description',
        'attachment',
        'note',
        'type',
        'way_entry',
        'status',
        'priority',
    ];

    // Ensure default values if not passed
    protected $attributes = [
        'type' => 'enquery',
        'way_entry' => 'email',
        'status' => 'opened',
        'priority' => 'normal',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    // Accessor to return full URL
    protected $appends = ['attachment_url'];

    public function getAttachmentUrlAttribute()
    {
        return $this->attachment
            ? asset('storage/' . $this->attachment)
            : null;
    }


    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'case_employees','case_id','employee_id');
    }
}
