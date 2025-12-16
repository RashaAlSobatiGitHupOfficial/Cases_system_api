<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InboundEmail extends Model
{
    protected $fillable = [
        'provider',
        'gmail_message_id',
        'gmail_thread_id',
        'from_email',
        'from_name',
        'subject',
        'received_at',
        'status',
        'client_id', 
        'case_id',
        'raw_headers',
        'raw_body',

    ];

    protected $casts = [
        'raw_headers' => 'array',
        'received_at' => 'datetime',
    ];
    public function client()
    {
        return $this->belongsTo(\App\Models\Client::class);
    }
    public function case()
    {
        return $this->belongsTo(CaseModel::class);
    }
}
