<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryDetails extends Model
{
    protected $fillable = [
        'service_id',
        'delivery_type',
        'details',
        'price',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
