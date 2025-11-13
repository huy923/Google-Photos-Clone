<?php

namespace App\Http\Controllers;

use App\Models\Album;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AlbumController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Album::with(['user', 'mediaFiles']);

        // Search functionality
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->get('user_id'));
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->get('type'));
        }

        // Filter by public status
        if ($request->has('is_public')) {
            $query->where('is_public', $request->boolean('is_public'));
        }

        // Filter by deleted status
        if ($request->has('is_deleted')) {
            $query->where('is_deleted', $request->boolean('is_deleted'));
        } else {
            // By default, show only non-deleted albums
            $query->notDeleted();
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $albums = $query->paginate($perPage);

        return response()->json($albums);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'cover_photo_path' => 'nullable|string|max:255',
            'type' => 'required|in:manual,auto',
            'auto_criteria' => 'nullable|array',
            'is_public' => 'boolean',
            'is_deleted' => 'boolean',
        ]);

        $album = Album::create($validated);

        return response()->json($album->load(['user', 'mediaFiles']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Album $album): JsonResponse
    {
        return response()->json($album->load(['user', 'mediaFiles', 'shares']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Album $album): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:1000',
            'cover_photo_path' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:manual,auto',
            'auto_criteria' => 'sometimes|array',
            'is_public' => 'sometimes|boolean',
            'is_deleted' => 'sometimes|boolean',
        ]);

        $album->update($validated);

        return response()->json($album->load(['user', 'mediaFiles']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Album $album): JsonResponse
    {
        // Soft delete by setting is_deleted to true
        $album->update(['is_deleted' => true, 'deleted_at' => now()]);

        return response()->json(['message' => 'Album deleted successfully']);
    }

    /**
     * Restore a soft-deleted album.
     */
    public function restore(Album $album): JsonResponse
    {
        $album->update(['is_deleted' => false, 'deleted_at' => null]);

        return response()->json($album->load(['user', 'mediaFiles']));
    }

    /**
     * Add media files to album.
     */
    public function addMedia(Request $request, Album $album): JsonResponse
    {
        $validated = $request->validate([
            'media_ids' => 'required|array',
            'media_ids.*' => 'exists:media_files,id'
        ]);

        // Attach media files to album (avoid duplicates)
        foreach ($validated['media_ids'] as $mediaId) {
            $album->mediaFiles()->syncWithoutDetaching([$mediaId]);
        }

        return response()->json($album->load(['mediaFiles']));
    }

    /**
     * Remove media files from album.
     */
    public function removeMedia(Request $request, Album $album): JsonResponse
    {
        $validated = $request->validate([
            'media_ids' => 'required|array',
            'media_ids.*' => 'exists:media_files,id'
        ]);

        $album->mediaFiles()->detach($validated['media_ids']);

        return response()->json($album->load(['mediaFiles']));
    }
}