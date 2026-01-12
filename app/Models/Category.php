<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'name',
        'image',
    ];

    /**
     * Get the category image URL.
     * Returns default image if no image is set.
     */
    public function getImageAttribute($value)
    {
        return $value ? url($value) : url('/images/default/noimage.jpg');
    }

    public function services()
    {
        return $this->hasMany(Service::class);
    }
}
