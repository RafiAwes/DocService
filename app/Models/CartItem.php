<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $fillable = [
        'cart_id',
        'total_price',
        'service_id',
        'quantity',
        'delivery_details_ids',
    ];

    // Auto-convert JSON to Array when you use $cartItem->delivery_details_ids
    protected $casts = [
        'delivery_details_ids' => 'array',
        'quantity' => 'integer',
    ];

    /**
     * Relationship: The Parent Cart
     */
    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * Relationship: The Service being bought
     */
    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Relationship: The Dynamic Answers
     * This looks into the 'answers' table where 'cart_item_id' matches this ID.
     */
    public function answers()
    {
        return $this->hasMany(Answers::class, 'cart_item_id');
    }
}
