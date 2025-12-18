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
        'location_id',
    ];

    protected $casts = [
        'waktu_mulai' => 'integer',
        'waktu_selesai' => 'integer',
        'worker_id' => 'integer',
        'jobdesc_id' => 'integer',
        'superfisor_id' => 'integer',
        'location_id' => 'integer',
    ];

    // Ensure location_id is always set
    protected $attributes = [
        'location_id' => null,
    ];

    public function worker()
    {
        return $this->belongsTo(Worker::class, 'worker_id');
    }

    public function jobdesc()
    {
        return $this->belongsTo(Jobdesc::class, 'jobdesc_id');
    }

    public function supervisor()
    {
        return $this->belongsTo(Worker::class, 'superfisor_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }
}
