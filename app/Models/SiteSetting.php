<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = [
        'site_name',
        'contact_email',
        'contact_phone',
        'address',
        'description',
        'facebook_url',
        'instagram_url',
        'tiktok_url',
        'youtube_url',
    ];
}
