<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'document',
        'name',
        'address',
        'phone',
        'email',
        'user',
        'password',
        'role',
        'state',
        'deleted'
    ];

    protected $hidden = [
        'password'
    ];

    public $timestamps = false;

    public function scopeSeller($query){
        return $query->where('role', 'seller');
    }

    public function scopeActive($query){
        return $query->where('deleted', 0);
    }

    public function hasRole(...$roles){
        $list = array_map('trim', $roles);
        return in_array($this->role, $list, true);
    }
}
