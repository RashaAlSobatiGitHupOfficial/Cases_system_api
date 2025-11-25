<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermissionCategory extends Model
{
     use HasFactory;

    protected $table = 'permissions_category';

    protected $fillable = [
        'category_name',
    ];

    public function permissions()
    {
        return $this->hasMany(Permission::class, 'category_id');
    }
}
