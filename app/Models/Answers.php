<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Answers extends Model
{
    protected $guarded = [

    ];

    protected $casts = [
        'delivery_details_ids' => 'array', // Converting JSON <-> Array automatically
        'south_african'        => 'boolean',
    ];

    public function serviceQuote()
    {
        return $this->belongsTo(ServiceQuote::class);
    }
}
