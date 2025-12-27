<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Answer;
use App\Models\DeliveryDetails;

class ServiceQuote extends Model
{
    protected $fillable = [
        'quote_id',
        'order_id',
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

    /**
     * Get the delivery details for the service quote.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function deliveryDetails(): HasMany
    {
        return $this->hasMany(DeliveryDetails::class, 'service_quote_id', 'id');
    }

    public function answers()
    {
        return $this->hasMany(Answer::class);
    }
}
