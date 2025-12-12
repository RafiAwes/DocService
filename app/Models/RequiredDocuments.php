<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequiredDocuments extends Model
{
    public $fillable = [
        'service_id',
        'title',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}