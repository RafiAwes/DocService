<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    protected $fillable = [
        'title',
        'description',
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

    protected function image(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                // Return default image if no image exists
                if (! $value) {
                    return url('/images/default/noimage.jpg');
                }

                // Check if it's already a complete URL (e.g. from a seeder)
                if (filter_var($value, FILTER_VALIDATE_URL)) {
                    return $value;
                }

                // Generate the full public URL
                // Ensure you have run: php artisan storage:link
                return url('images/news/'.$value);
            }
        );
    }
}
