<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quote extends Model
{
    protected $fillable = [
        'user_id',
        'type',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
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
