<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Answers extends Model
{
    protected $guarded = [

    ];

    protected $casts = [
        'delivery_details_ids' => 'array',
        'south_african'        => 'boolean',
    ];

    public function serviceQuote()
    {
        return $this->belongsTo(ServiceQuote::class);
    }

    public function questionary()
    {
        return $this->belongsTo(Questionaries::class, 'questionary_id');
    }
}
