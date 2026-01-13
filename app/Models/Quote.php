<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Quote extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'delivery_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }

    public function customQuote()
    {
        return $this->hasOne(CustomQuote::class);
    }

    public function serviceQuote()
    {
        return $this->hasOne(ServiceQuote::class);
    }
}
