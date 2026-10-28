<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    // Show approved reviews publicly.
    public function index()
    {
        $reviews = Review::where('is_approved', true)
            ->latest()
            ->get();

        return response()->json($reviews);
    }

    // Submit a customer review.
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'tour' => ['required', 'string', 'max:255'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['required', 'string', 'max:5000'],
        ]);

        $review = Review::create($validated);

        return response()->json([
            'message' => 'Review submitted successfully. It is awaiting approval.',
            'review' => $review,
        ], 201);
    }

    // Show all reviews in the admin panel.
    public function adminIndex()
    {
        $reviews = Review::latest()->get();

        return response()->json($reviews);
    }

    // Approve or reject a review.
    public function updateApproval(Request $request, string $id)
    {
        $validated = $request->validate([
            'is_approved' => ['required', 'boolean'],
        ]);

        $review = Review::findOrFail($id);
        $review->update($validated);

        return response()->json([
            'message' => 'Review approval status updated successfully.',
            'review' => $review,
        ]);
    }

    // Delete a review.
    public function destroy(string $id)
    {
        $review = Review::findOrFail($id);
        $review->delete();

        return response()->json([
            'message' => 'Review deleted successfully.',
        ]);
    }
}
