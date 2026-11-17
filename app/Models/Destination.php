<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Destination extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'image',
        'location',
        'latitude',
        'longitude',
        'best_time',
        'duration',
        'highlights',
        'is_published',
    ];

    public function tours(): HasMany
    {
        return $this->hasMany(Tour::class);
    }
}
