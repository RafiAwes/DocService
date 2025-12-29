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
    ] ;

    public function category() {
        return $this->belongsTo(Category::class,'category_id');
    }

    public function includedServices() {
        return $this->hasMany(IncludedService::class);
    }

    public function processingTimes() {
        return $this->hasMany(ProcessingTime::class);
    }

    public function deliveryDetails() {
        return $this->hasMany(DeliveryDetails::class);
    }

    public function questionaries() {
        return $this->hasMany(Questionaries::class);
    }

    public function requiredDocuments() {
        return $this->hasMany(RequiredDocuments::class);
    }
}
