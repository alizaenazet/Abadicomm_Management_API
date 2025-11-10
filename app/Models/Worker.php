<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class Worker extends Model
{
    use HasApiTokens,HasFactory;

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
