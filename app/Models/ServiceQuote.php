<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceQuote extends Model
{
    protected $fillable = [
        'quote_id',
        'service_id',
    ];

    public function quote()
    {
        return $this->belongsTo(Quote::class);
    }

    public function answer()
    {
        return $this->hasOne(Answers::class, 'service_quote_id');
    }
}
