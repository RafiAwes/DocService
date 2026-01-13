<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Answers;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'service_id',
        'quantity',
        'price',
        'subtotal',
    ];
    protected $casts = [
        'price' => 'decimal:2',
        'quantity' => 'integer',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function getTotalPriceAttribute()
    {
        return $this->quantity * $this->price;
    }

    /**
     * Answers captured for this order item.
     */
    public function answers(): HasMany
    {
        return $this->hasMany(Answers::class, 'order_item_id');
    }

}
