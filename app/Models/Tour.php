<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tour extends Model
{
    protected $fillable = [
    'name',
    'slug',
    'description',
    'duration',
    'difficulty',
    'price',
    'category',
    'image',
    'highlights',
    'itinerary',
    'gallery_images',
    'is_published',
];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'itinerary' => 'array',
            'gallery_images' => 'array',
            'is_published' => 'boolean',
        ];
    }
}
