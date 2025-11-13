<?php

namespace App\Http\Controllers;

use App\Models\Share;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ShareController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Share::with(['user', 'shareable', 'shareAccess']);

        // Search functionality
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where('share_token', 'like', "%{$search}%");
        }

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->get('user_id'));
        }

        // Filter by shareable type
        if ($request->has('shareable_type')) {
            $query->where('shareable_type', $request->get('shareable_type'));
        }

        // Filter by shareable ID
        if ($request->has('shareable_id')) {
            $query->where('shareable_id', $request->get('shareable_id'));
        }

        // Filter by permission
        if ($request->has('permission')) {
            $query->where('permission', $request->get('permission'));
        }

        // Filter by access type
        if ($request->has('access_type')) {
            $query->where('access_type', $request->get('access_type'));
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Filter by expired status
        if ($request->has('expired')) {
            if ($request->boolean('expired')) {
                $query->where('expires_at', '<', now());
            } else {
                $query->notExpired();
            }
        }

        // Order by creation date (newest first)
        $query->orderBy('created_at', 'desc');

        // Pagination
        $perPage = $request->get('per_page', 15);
        $shares = $query->paginate($perPage);

        return response()->json($shares);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'shareable_type' => 'required|in:App\Models\MediaFile,App\Models\Album',
            'shareable_id' => 'required|integer',
            'permission' => 'required|in:view,download,edit',
            'access_type' => 'required|in:public,private,password',
            'expires_at' => 'nullable|date|after:now',
            'is_active' => 'boolean',
        ]);

        // Verify that the shareable item exists
        $shareableClass = $validated['shareable_type'];
        $shareable = $shareableClass::find($validated['shareable_id']);
        
        if (!$shareable) {
            return response()->json(['message' => 'Shareable item not found'], 404);
        }

        $share = Share::create($validated);

        return response()->json($share->load(['user', 'shareable', 'shareAccess']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Share $share): JsonResponse
    {
        return response()->json($share->load(['user', 'shareable', 'shareAccess']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Share $share): JsonResponse
    {
        $validated = $request->validate([
            'permission' => 'sometimes|in:view,download,edit',
            'access_type' => 'sometimes|in:public,private,password',
            'expires_at' => 'sometimes|date|after:now',
            'is_active' => 'sometimes|boolean',
        ]);

        $share->update($validated);

        return response()->json($share->load(['user', 'shareable', 'shareAccess']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Share $share): JsonResponse
    {
        $share->delete();

        return response()->json(['message' => 'Share deleted successfully']);
    }

    /**
     * Get share by token (for public access).
     */
    public function getByToken($token): JsonResponse
    {
        $share = Share::where('share_token', $token)
            ->active()
            ->notExpired()
            ->with(['user', 'shareable', 'shareAccess'])
            ->first();

        if (!$share) {
            return response()->json(['message' => 'Share not found or expired'], 404);
        }

        return response()->json($share);
    }

    /**
     * Increment view count for a share.
     */
    public function incrementViews(Share $share): JsonResponse
    {
        $share->increment('view_count');

        return response()->json([
            'view_count' => $share->view_count
        ]);
    }
}