<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = ['client_name', 'address', 'logo', 'email'];


    public function cases()
    {
        return $this->hasMany(CaseModel::class);
    }
}
