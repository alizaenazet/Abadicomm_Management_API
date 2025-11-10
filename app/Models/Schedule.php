<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'waktu_mulai',
        'waktu_selesai',
        'worker_id',
        'jobdesc_id',
        'superfisor_id',
        'tempat',
    ];

    protected $casts = [
        'waktu_mulai' => 'integer',
        'waktu_selesai' => 'integer',
    ];

    public function worker()
    {
        return $this->belongsTo(Worker::class);
    }

    public function jobdesc()
    {
        return $this->belongsTo(Jobdesc::class);
    }

    public function supervisor()
    {
        return $this->belongsTo(Worker::class, 'superfisor_id');
    }
}
