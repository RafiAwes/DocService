<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Answers extends Model
{
    protected $fillable = [
        'user_id',          // <--- Who
        'order_id',         // <--- Which Order
        'order_item_id',    // <--- Which specific Service in that order
        'questionary_id',   // <--- Which Question
        'value'             // <--- The Answer
    ];

    protected $casts = [
        'delivery_details_ids' => 'array',
        // 'south_african'        => 'boolean',
    ];

    public function serviceQuote()
    {
        return $this->belongsTo(ServiceQuote::class);
    }

    public function questionary()
    {
        return $this->belongsTo(Questionaries::class, 'questionary_id');
    }

    /**
     * Return full URL for stored file paths while leaving plain text untouched.
     */
    public function getValueAttribute($value)
    {
        if (empty($value)) {
            return $value;
        }

        // Already a full URL
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }

        // Heuristics: treat known storage-like paths as files
        if (Str::startsWith($value, ['documents/', 'images/', 'uploads/', 'public/'])) {
            return asset('storage/' . ltrim($value, '/'));
        }

        return $value;
    }
}
