<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Answers;

class Questionaries extends Model
{
    public $fillable = [
        'service_id',
        'name',
        'type',
        'options',
    ];

    protected $casts = [
        'options' => 'array',
    ];

    /**
     * Normalized type accessor (e.g., "Input field" -> "inputfield").
     */
    public function getNormalizedTypeAttribute(): string
    {
        return strtolower(str_replace(' ', '', $this->type ?? ''));
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Answers submitted for this question.
     */
    public function answers(): HasMany
    {
        return $this->hasMany(Answers::class, 'questionary_id');
    }
}
