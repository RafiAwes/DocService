<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'category_id',
        'title',
        'order_type',
        'price',
        'description',
    ] ;

    public function category()
    {
        return $this->belongsToMany(Category::class);
    }

    public function includedServices()
    {
        return $this->hasMany(IncludedService::class);
    }
}
