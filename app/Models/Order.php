<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use App\Models\{Answers, Transaction};

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'orderid',
        'is_south_africa',
        'stripe_payment_id',
        'total_amount',
        'status',
        'delivery_id',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'is_south_africa' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }
    
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function answer()
    {
        return $this->hasMany(Answers::class);
    }

    /**
     * Transactions linked to this order.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public static function generateOrderId()
    {
        // Get the latest order ID
        $latestOrder = self::orderBy('orderid', 'desc')->first();
        
        if ($latestOrder) {
            // Increment the latest order ID
            $nextNumber = intval($latestOrder->orderid) + 1;
        } else {
            // First order starts at 1
            $nextNumber = 1;
        }
        
        // Pad with leading zeros to ensure 6 digits
        $orderid = str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
        
        return $orderid;
    }

     public function rating(){
        return $this->hasOne(Rating::class);
     }

}
