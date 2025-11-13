<?php

namespace App\Http\Controllers;

use App\Models\MediaView;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MediaViewController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = MediaView::with(['user', 'mediaFile']);

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->get('user_id'));
        }

        // Filter by media file
        if ($request->has('media_file_id')) {
            $query->where('media_file_id', $request->get('media_file_id'));
        }

        // Filter by IP address
        if ($request->has('ip_address')) {
            $query->where('ip_address', $request->get('ip_address'));
        }

        // Date range filter
        if ($request->has('date_from')) {
            $query->where('created_at', '>=', $request->get('date_from'));
        }
        if ($request->has('date_to')) {
            $query->where('created_at', '<=', $request->get('date_to'));
        }

        // Order by creation date (newest first)
        $query->orderBy('created_at', 'desc');

        // Pagination
        $perPage = $request->get('per_page', 15);
        $views = $query->paginate($perPage);

        return response()->json($views);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'media_file_id' => 'required|exists:media_files,id',
            'user_id' => 'nullable|exists:users,id',
            'ip_address' => 'required|ip',
            'user_agent' => 'nullable|string|max:500',
        ]);

        // Get user IP if not provided
        if (empty($validated['ip_address'])) {
            $validated['ip_address'] = $request->ip();
        }

        // Get user agent if not provided
        if (empty($validated['user_agent'])) {
            $validated['user_agent'] = $request->userAgent();
        }

        $view = MediaView::create($validated);

        return response()->json($view->load(['user', 'mediaFile']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(MediaView $mediaView): JsonResponse
    {
        return response()->json($mediaView->load(['user', 'mediaFile']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MediaView $mediaView): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'sometimes|exists:users,id',
            'ip_address' => 'sometimes|ip',
            'user_agent' => 'sometimes|string|max:500',
        ]);

        $mediaView->update($validated);

        return response()->json($mediaView->load(['user', 'mediaFile']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MediaView $mediaView): JsonResponse
    {
        $mediaView->delete();

        return response()->json(['message' => 'Media view deleted successfully']);
    }

    /**
     * Get view statistics for a media file.
     */
    public function statistics(Request $request, $mediaFileId): JsonResponse
    {
        $query = MediaView::where('media_file_id', $mediaFileId);

        // Date range filter
        if ($request->has('date_from')) {
            $query->where('created_at', '>=', $request->get('date_from'));
        }
        if ($request->has('date_to')) {
            $query->where('created_at', '<=', $request->get('date_to'));
        }

        $totalViews = $query->count();
        $uniqueUsers = $query->distinct('user_id')->count('user_id');
        $uniqueIPs = $query->distinct('ip_address')->count('ip_address');

        return response()->json([
            'media_file_id' => $mediaFileId,
            'total_views' => $totalViews,
            'unique_users' => $uniqueUsers,
            'unique_ips' => $uniqueIPs,
        ]);
    }
}