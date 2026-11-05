<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tour;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TourController extends Controller
{
    // Public: published tours
    public function index()
    {
        return response()->json(
            Tour::where('is_published', true)->latest()->get()
        );
    }

    // Admin: all tours
    public function adminIndex()
    {
        return response()->json(Tour::latest()->get());
    }

    // Admin: single tour
    public function adminShow(string $id)
    {
        return response()->json(Tour::findOrFail($id));
    }

    // Public: single published tour
    public function show(string $slug)
    {
        $tour = Tour::where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        return response()->json($tour);
    }

    // Create tour
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:tours,slug',
            'description' => 'required|string',
            'duration' => 'required|string|max:255',
            'difficulty' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'category' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'highlights' => 'nullable|string',
            'itinerary' => 'nullable|array',
            'gallery_images' => 'nullable|array',
            'gallery_images.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048',
            'is_published' => 'sometimes|boolean',
        ]);

        $galleryFiles = $request->file('gallery_images', []);

        unset($validated['gallery_images']);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')
                ->store('tours', 'public');
        }

        $validated['gallery_images'] = [];

        foreach ($galleryFiles as $file) {
            $validated['gallery_images'][] = $file
                ->store('tours/gallery', 'public');
        }

        $tour = Tour::create($validated);

        return response()->json([
            'message' => 'Tour created successfully.',
            'tour' => $tour,
        ], 201);
    }

    // Update tour
    public function update(Request $request, string $id)
    {
        $tour = Tour::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|required|string|max:255|unique:tours,slug,' . $id,
            'description' => 'sometimes|required|string',
            'duration' => 'sometimes|required|string|max:255',
            'difficulty' => 'sometimes|required|string|max:255',
            'price' => 'sometimes|required|numeric|min:0',
            'category' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'highlights' => 'nullable|string',
            'itinerary' => 'nullable|array',
            'gallery_images' => 'nullable|array',
            'gallery_images.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048',
            'is_published' => 'sometimes|boolean',
        ]);

        $galleryFiles = $request->file('gallery_images', []);

        unset($validated['gallery_images']);

        if ($request->hasFile('image')) {
            if ($tour->image) {
                Storage::disk('public')->delete($tour->image);
            }

            $validated['image'] = $request->file('image')
                ->store('tours', 'public');
        }

        $galleryImages = $tour->gallery_images ?? [];

        foreach ($galleryFiles as $file) {
            $galleryImages[] = $file
                ->store('tours/gallery', 'public');
        }

        $validated['gallery_images'] = $galleryImages;

        $tour->update($validated);

        return response()->json([
            'message' => 'Tour updated successfully.',
            'tour' => $tour->fresh(),
        ]);
    }

    // Publish / unpublish
    public function togglePublish(string $id)
    {
        $tour = Tour::findOrFail($id);

        $tour->update([
            'is_published' => !$tour->is_published,
        ]);

        return response()->json([
            'message' => $tour->is_published
                ? 'Tour published successfully.'
                : 'Tour unpublished successfully.',
            'tour' => $tour,
        ]);
    }

    // Delete tour and its images
    public function destroy(string $id)
    {
        $tour = Tour::findOrFail($id);

        if ($tour->image) {
            Storage::disk('public')->delete($tour->image);
        }

        foreach ($tour->gallery_images ?? [] as $galleryImage) {
            Storage::disk('public')->delete($galleryImage);
        }

        $tour->delete();

        return response()->json([
            'message' => 'Tour deleted successfully.',
        ]);
    }
}
