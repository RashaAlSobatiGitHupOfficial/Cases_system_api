<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = ['permission_name', 'category_id'];


     public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }
    
     public function category()
    {
        return $this->belongsTo(PermissionCategory::class, 'category_id');
    }
}
