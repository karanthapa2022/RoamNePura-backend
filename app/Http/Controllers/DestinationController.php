<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Destination;
use Illuminate\Support\Facades\Storage;

class DestinationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
{
    $destinations = Destination::where('is_published', true)->get();

    return response()->json($destinations);
}

public function adminIndex()
{
    $destinations = Destination::latest()->get();

    return response()->json($destinations);
}

public function adminShow(string $id)
{
    $destination = Destination::findOrFail($id);

    return response()->json($destination);
}

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'slug' => 'required|string|max:255|unique:destinations,slug',
        'description' => 'required|string',
        'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        'location' => 'nullable|string|max:255',
        'best_time' => 'nullable|string|max:255',
        'duration' => 'nullable|string|max:255',
        'highlights' => 'nullable|string',
        'is_published' => 'boolean',
    ]);

    if ($request->hasFile('image')) {
    $validated['image'] = $request->file('image')->store('destinations', 'public');
}
    $destination = Destination::create($validated);

    return response()->json($destination, 201);
}

    /**
     * Display the specified resource.
     */
    public function show(string $id)
{
    $destination = Destination::where('id', $id)
        ->where('is_published', true)
        ->firstOrFail();

    return response()->json($destination);
}

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
{
    $destination = Destination::findOrFail($id);

    $validated = $request->validate([
        'name' => 'sometimes|required|string|max:255',
        'slug' => 'sometimes|required|string|max:255|unique:destinations,slug,' . $id,
        'description' => 'sometimes|required|string',
        'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        'location' => 'nullable|string|max:255',
        'best_time' => 'nullable|string|max:255',
        'duration' => 'nullable|string|max:255',
        'highlights' => 'nullable|string',
        'is_published' => 'boolean',
    ]);

    if ($request->hasFile('image')) {
    if ($destination->image) {
        Storage::disk('public')->delete($destination->image);
    }

    $validated['image'] = $request->file('image')->store('destinations', 'public');
}

$destination->update($validated);

return response()->json($destination);
}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
{
    $destination = Destination::findOrFail($id);

    $destination->delete();

    return response()->json([
        'message' => 'Destination deleted successfully.'
    ]);
}
}
