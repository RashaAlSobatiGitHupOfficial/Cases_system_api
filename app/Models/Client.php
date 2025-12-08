<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = ['client_name', 'address', 'email'];
    protected $casts = [
    'logo' => 'string',
];



    public function cases()
    {
        return $this->hasMany(CaseModel::class);
    }
}
