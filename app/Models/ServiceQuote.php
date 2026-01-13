<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Answers;

class ServiceQuote extends Model
{
    protected $fillable = [
        'quote_id',
        'order_id',
        'service_id',
    ];

    protected $casts = [];

    public function quote()
    {
        return $this->belongsTo(Quote::class);
    }
    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function answers()
    {
        return $this->hasMany(Answers::class);
    }
}
