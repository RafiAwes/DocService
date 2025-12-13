<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomQuote extends Model
{
    protected $fillable = [
        'quote_id',
        'name',
        'email',
        'contact_number',
        'document_request',
        'drc',
        'duc',
        'residence_country',
    ];

    public function quote()
    {
        return $this->belongsTo(Quote::class);
    }
}
