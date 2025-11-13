<?php

namespace App\Http\Controllers;

use App\Models\MediaMetadata;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MediaMetadataController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = MediaMetadata::with('mediaFile');

        // Search functionality
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('camera_make', 'like', "%{$search}%")
                  ->orWhere('camera_model', 'like', "%{$search}%")
                  ->orWhere('lens_model', 'like', "%{$search}%")
                  ->orWhere('location_name', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('country', 'like', "%{$search}%");
            });
        }

        // Filter by media file
        if ($request->has('media_file_id')) {
            $query->where('media_file_id', $request->get('media_file_id'));
        }

        // Filter by camera make
        if ($request->has('camera_make')) {
            $query->where('camera_make', $request->get('camera_make'));
        }

        // Filter by camera model
        if ($request->has('camera_model')) {
            $query->where('camera_model', $request->get('camera_model'));
        }

        // Filter by location
        if ($request->has('city')) {
            $query->where('city', 'like', '%' . $request->get('city') . '%');
        }

        if ($request->has('country')) {
            $query->where('country', 'like', '%' . $request->get('country') . '%');
        }

        // Date range filter
        if ($request->has('taken_from')) {
            $query->where('taken_at', '>=', $request->get('taken_from'));
        }
        if ($request->has('taken_to')) {
            $query->where('taken_at', '<=', $request->get('taken_to'));
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $metadata = $query->paginate($perPage);

        return response()->json($metadata);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'media_file_id' => 'required|exists:media_files,id|unique:media_metadata,media_file_id',
            'taken_at' => 'nullable|date',
            'camera_make' => 'nullable|string|max:100',
            'camera_model' => 'nullable|string|max:100',
            'lens_model' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'location_name' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'altitude' => 'nullable|numeric',
            'focal_length' => 'nullable|numeric|min:0',
            'aperture' => 'nullable|numeric|min:0',
            'shutter_speed' => 'nullable|string|max:50',
            'iso' => 'nullable|integer|min:0',
            'flash' => 'nullable|boolean',
            'white_balance' => 'nullable|string|max:50',
            'exif_data' => 'nullable|array',
        ]);

        $metadata = MediaMetadata::create($validated);

        return response()->json($metadata->load('mediaFile'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(MediaMetadata $mediaMetadata): JsonResponse
    {
        return response()->json($mediaMetadata->load('mediaFile'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MediaMetadata $mediaMetadata): JsonResponse
    {
        $validated = $request->validate([
            'taken_at' => 'sometimes|date',
            'camera_make' => 'sometimes|string|max:100',
            'camera_model' => 'sometimes|string|max:100',
            'lens_model' => 'sometimes|string|max:100',
            'latitude' => 'sometimes|numeric|between:-90,90',
            'longitude' => 'sometimes|numeric|between:-180,180',
            'location_name' => 'sometimes|string|max:255',
            'city' => 'sometimes|string|max:100',
            'country' => 'sometimes|string|max:100',
            'altitude' => 'sometimes|numeric',
            'focal_length' => 'sometimes|numeric|min:0',
            'aperture' => 'sometimes|numeric|min:0',
            'shutter_speed' => 'sometimes|string|max:50',
            'iso' => 'sometimes|integer|min:0',
            'flash' => 'sometimes|boolean',
            'white_balance' => 'sometimes|string|max:50',
            'exif_data' => 'sometimes|array',
        ]);

        $mediaMetadata->update($validated);

        return response()->json($mediaMetadata->load('mediaFile'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MediaMetadata $mediaMetadata): JsonResponse
    {
        $mediaMetadata->delete();

        return response()->json(['message' => 'Media metadata deleted successfully']);
    }
}