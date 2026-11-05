<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;

class SiteSettingController extends Controller
{
    // Public: Get website information
    public function show()
    {
        $settings = SiteSetting::first();

        if (!$settings) {
            $settings = SiteSetting::create([
                'site_name' => 'RoamNePura',
            ]);
        }

        return response()->json([
            'settings' => $settings,
        ]);
    }

    // Admin: Update website information
    public function update(Request $request)
    {
        $validated = $request->validate([
            'site_name' => ['required', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string', 'max:2000'],
            'facebook_url' => ['nullable', 'url', 'max:255'],
            'instagram_url' => ['nullable', 'url', 'max:255'],
            'tiktok_url' => ['nullable', 'url', 'max:255'],
            'youtube_url' => ['nullable', 'url', 'max:255'],
        ]);

        $settings = SiteSetting::first();

        if (!$settings) {
            $settings = SiteSetting::create($validated);
        } else {
            $settings->update($validated);
        }

        return response()->json([
            'message' => 'Website information updated successfully.',
            'settings' => $settings->fresh(),
        ]);
    }
}
