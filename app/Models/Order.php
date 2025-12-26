<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'slug',
        'is_south_africa',
        'stripe_payment_id',
        'total_amount',
        'status',
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
        return $this->hasOne(Answers::class);
    }

    public static function generateSlug()
    {
        do{
            $slug = random_int(100000, 999999);
        } while (self::where('slug', $slug)->exists());

        return $slug;
    }

     public function rating(){
        return $this->hasOne(Rating::class);
     }

}
