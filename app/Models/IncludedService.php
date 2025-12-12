<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncludedService extends Model
{
    protected $fillable = [
        'service_id',
        'service_type',
        'included_details',
        'price',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
