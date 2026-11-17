<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Destination;
use Illuminate\Support\Facades\Storage;

class DestinationController extends Controller
{
    // Public: published destinations
    public function index()
    {
        return response()->json(
            Destination::where('is_published', true)->latest()->get()
        );
    }

    // Admin: all destinations
    public function adminIndex()
    {
        return response()->json(Destination::latest()->get());
    }

    // Admin: single destination
    public function adminShow(string $id)
    {
        return response()->json(Destination::findOrFail($id));
    }

    // Create destination
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:destinations,slug',
            'description' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'location' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'best_time' => 'nullable|string|max:255',
            'duration' => 'nullable|string|max:255',
            'highlights' => 'nullable|string',
            'is_published' => 'boolean',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')
                ->store('destinations', 'public');
        }

        $destination = Destination::create($validated);

        return response()->json($destination, 201);
    }

    // Public: single published destination
    public function show(string $slug)
    {
        $destination = Destination::where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        return response()->json($destination);
    }

    // Update destination
    public function update(Request $request, string $id)
    {
        $destination = Destination::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|required|string|max:255|unique:destinations,slug,' . $id,
            'description' => 'sometimes|required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'location' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'best_time' => 'nullable|string|max:255',
            'duration' => 'nullable|string|max:255',
            'highlights' => 'nullable|string',
            'is_published' => 'boolean',
        ]);

        if ($request->hasFile('image')) {
            if ($destination->image) {
                Storage::disk('public')->delete($destination->image);
            }

            $validated['image'] = $request->file('image')
                ->store('destinations', 'public');
        }

        $destination->update($validated);

        return response()->json($destination);
    }

    // Publish / unpublish
    public function togglePublish(string $id)
    {
        $destination = Destination::findOrFail($id);

        $destination->update([
            'is_published' => !$destination->is_published,
        ]);

        return response()->json([
            'message' => $destination->is_published
                ? 'Destination published successfully.'
                : 'Destination unpublished successfully.',
            'destination' => $destination,
        ]);
    }

    // Delete destination
    public function destroy(string $id)
    {
        $destination = Destination::findOrFail($id);

        if ($destination->image) {
            Storage::disk('public')->delete($destination->image);
        }

        $destination->delete();

        return response()->json([
            'message' => 'Destination deleted successfully.',
        ]);
    }
}
