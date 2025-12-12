<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomQuote extends Model
{
    protected $fillable = [
        'quote_id',
        'email',
        'contact_number',
        'doc_request',
    ];
}
