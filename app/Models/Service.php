<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'category_id',
        'is_south_african',
        'title',
        'subtitle',
        'order_type',
        'type',
        'price',
        'description',
        'short_description',
        'image',
    ];

    public function getImageAttribute($value)
    {
        if (!$value) {
            return url('images/default/noimage.jpg');
        }
        
        // If it's already a full URL, return as is
        if (str_starts_with($value, 'http')) {
            return $value;
        }
        
        // If it's just the filename, construct the full path
        if (!str_starts_with($value, 'images/')) {
            return url('images/service/' . $value);
        }
        
        // If it's already a path like 'images/service/xxx.jpg'
        return url($value);
    }

    public function category() {
        return $this->belongsTo(Category::class,'category_id');
    }

    public function includedServices() {
        return $this->hasMany(IncludedService::class);
    }

    public function processingTimes() {
        return $this->hasMany(ProcessingTime::class);
    }

    public function questionaries() {
        return $this->hasMany(Questionaries::class);
    }

    public function requiredDocuments() {
        return $this->hasMany(RequiredDocuments::class);
    }

    public function howItWorks() {
        return $this->hasMany(HowItWorks::class);
    }
}
