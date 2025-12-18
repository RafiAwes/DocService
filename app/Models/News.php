<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    protected $fillable = [
        'title',
        'Description',
        'image',
    ];
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getSummaryAttribute()
    {
        return \Str::limit($this->description, 150);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

}
