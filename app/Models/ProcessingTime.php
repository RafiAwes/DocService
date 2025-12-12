<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcessingTime extends Model
{
    protected $fillable = [
        'service_id',
        'details',
        'time',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
