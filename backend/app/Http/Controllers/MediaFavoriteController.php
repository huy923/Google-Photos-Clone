<?php

namespace App\Http\Controllers;

use App\Models\MediaFavorite;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MediaFavoriteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = MediaFavorite::with(['user', 'mediaFile']);

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->get('user_id'));
        }

        // Filter by media file
        if ($request->has('media_file_id')) {
            $query->where('media_file_id', $request->get('media_file_id'));
        }

        // Order by creation date (newest first)
        $query->orderBy('created_at', 'desc');

        // Pagination
        $perPage = $request->get('per_page', 15);
        $favorites = $query->paginate($perPage);

        return response()->json($favorites);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'media_file_id' => 'required|exists:media_files,id',
            'user_id' => 'required|exists:users,id',
        ]);

        // Check if favorite already exists
        $existingFavorite = MediaFavorite::where('user_id', $validated['user_id'])
            ->where('media_file_id', $validated['media_file_id'])
            ->first();

        if ($existingFavorite) {
            return response()->json(['message' => 'Media file is already favorited'], 409);
        }

        $favorite = MediaFavorite::create($validated);

        return response()->json($favorite->load(['user', 'mediaFile']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(MediaFavorite $mediaFavorite): JsonResponse
    {
        return response()->json($mediaFavorite->load(['user', 'mediaFile']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MediaFavorite $mediaFavorite): JsonResponse
    {
        // MediaFavorite doesn't have updatable fields other than the foreign keys
        // which shouldn't be changed after creation
        return response()->json([
            'message' => 'Media favorite cannot be updated. Delete and create a new one if needed.'
        ], 400);
    }


    public function destroy(MediaFavorite $mediaFavorite): JsonResponse
    {
        $mediaFavorite->delete();

        return response()->json(['message' => 'Media favorite removed successfully']);
    }


    public function toggle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'media_file_id' => 'required|exists:media_files,id',
            'user_id' => 'required|exists:users,id',
        ]);

        $favorite = MediaFavorite::where('user_id', $validated['user_id'])
            ->where('media_file_id', $validated['media_file_id'])
            ->first();

        if ($favorite) {
            $favorite->delete();
            return response()->json(['message' => 'Media file unfavorited', 'is_favorited' => false]);
        } else {
            $favorite = MediaFavorite::create($validated);
            return response()->json([
                'message' => 'Media file favorited', 
                'is_favorited' => true,
                'favorite' => $favorite->load(['user', 'mediaFile'])
            ]);
        }
    }
}