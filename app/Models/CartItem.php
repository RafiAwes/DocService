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
    ];

    protected $casts = [
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
    // public function service()
    // {
    //     return $this->belongsTo(related: Service::class);

    // }

    public function service()
    {
        return $this->belongsTo(Service::class)->withDefault([
            'id' => null,
            'title' => null,
            'subtitle' => null,
            'type' => null,
            'order_type' => null,
            'price' => 0,
            'description' => null,
        ]);
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
