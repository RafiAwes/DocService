<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HowItWorks extends Model
{
    protected $table = 'how_it_works';

    protected $fillable = [
        'service_id',
        'title',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
