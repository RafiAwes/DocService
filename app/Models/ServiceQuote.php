<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceQuote extends Model
{
    protected $fillable = [
        'quote_id',
        'service_id',
        'delivery_details_ids',
    ];

    protected $casts = [
        'delivery_details_ids' => 'array',
    ];

    public function quote()
    {
        return $this->belongsTo(Quote::class);
    }
    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function deliveryDetails()
    {
        return $this->hasMany(DeliveryDetail::class, 'service_quote_id');
    }

    public function answer()
    {
        return $this->hasOne(Answers::class, 'service_quote_id');
    }
}
