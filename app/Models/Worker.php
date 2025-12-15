<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;


class Worker extends Authenticatable
{
    use HasApiTokens,HasFactory,Notifiable;

    protected $fillable = [
        'name',
        'password',
        'role_id',
        'email',
    ];

    protected $hidden = [
        'password',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function supervisedSchedules()
    {
        return $this->hasMany(Schedule::class, 'superfisor_id');
    }
}
