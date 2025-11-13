<?php

namespace App\Http\Controllers;

use App\Models\MediaTag;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MediaTagController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = MediaTag::withCount('mediaFiles');

        // Search functionality
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        // Filter by color
        if ($request->has('color')) {
            $query->where('color', $request->get('color'));
        }

        // Order by name
        $query->orderBy('name');

        // Pagination
        $perPage = $request->get('per_page', 15);
        $tags = $query->paginate($perPage);

        return response()->json($tags);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:media_tags,name',
            'slug' => 'nullable|string|max:100|unique:media_tags,slug',
            'color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        $tag = MediaTag::create($validated);

        return response()->json($tag, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(MediaTag $mediaTag): JsonResponse
    {
        return response()->json($mediaTag->loadCount('mediaFiles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MediaTag $mediaTag): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:100|unique:media_tags,name,' . $mediaTag->id,
            'slug' => 'sometimes|string|max:100|unique:media_tags,slug,' . $mediaTag->id,
            'color' => 'sometimes|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        $mediaTag->update($validated);

        return response()->json($mediaTag->loadCount('mediaFiles'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MediaTag $mediaTag): JsonResponse
    {
        // Check if tag is being used by any media files
        if ($mediaTag->mediaFiles()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete tag that is being used by media files'
            ], 400);
        }

        $mediaTag->delete();

        return response()->json(['message' => 'Media tag deleted successfully']);
    }

    /**
     * Get popular tags (most used).
     */
    public function popular(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);
        
        $tags = MediaTag::withCount('mediaFiles')
            ->orderBy('media_files_count', 'desc')
            ->limit($limit)
            ->get();

        return response()->json($tags);
    }
}