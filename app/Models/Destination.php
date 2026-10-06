<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Destination extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'image',
        'location',
        'best_time',
        'duration',
        'highlights',
        'is_published',
    ];
}
