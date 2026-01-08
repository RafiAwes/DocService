<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'is_south_africa' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
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
        do{
            $orderid = random_int(100000, 999999);
        } while (self::where('orderid', $orderid)->exists());

        return $orderid;
    }

     public function rating(){
        return $this->hasOne(Rating::class);
     }

}
