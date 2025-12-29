<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $fillable = [
        'user_id',
        // We don't need 'status' or 'total' here usually,
        // because a Cart is dynamic and calculated on the fly.
    ];

    /**
     * Relationship: A Cart belongs to a User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship: A Cart has many Items (Services)
     */
    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    // Optional Genius Helper: Calculate Total Price of Cart on the fly
    public function getTotalAttribute()
    {
        return $this->items->sum(function ($item) {
            return $item->quantity * $item->service->price;
        });
    }
}
