<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Answers extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        
        // Order Context
        'order_id',
        'order_item_id',
        
        // Cart Context
        'cart_id',
        'cart_item_id',
        
        // Quote Context
        'service_quote_id',
        
        // content
        'questionary_id',
        'value'
    ];

    /**
     * Genius Feature: Auto-format File URLs
     * When you access $answer->value, if it's a file path, return full URL
     */
    public function getValueAttribute($value)
    {
        // If the value looks like a stored file path (e.g. documents/...), return full URL
        if ($value && (str_starts_with($value, 'documents/') || str_starts_with($value, 'answers/'))) {
            return url('storage/' . $value);
        }
        return $value;
    }

    /**
     * Optional: Keep file_url for backward compatibility
     */
    protected $appends = ['file_url'];

    public function getFileUrlAttribute()
    {
        // Returns the same as value (for backward compatibility)
        return $this->value;
    }

    // --- RELATIONSHIPS ---

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function questionary()
    {
        return $this->belongsTo(Questionaries::class, 'questionary_id');
    }

    // Context Relationships
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function cartItem()
    {
        return $this->belongsTo(CartItem::class);
    }

    public function serviceQuote()
    {
        return $this->belongsTo(ServiceQuote::class);
    }
}
