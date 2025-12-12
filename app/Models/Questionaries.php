<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Questionaries extends Model
{
    public $fillable = [
        'service_id',
        'type',
        'options',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
