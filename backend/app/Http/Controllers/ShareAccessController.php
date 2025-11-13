<?php

namespace App\Http\Controllers;

use App\Models\ShareAccess;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ShareAccessController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ShareAccess::with(['share', 'user']);

        // Search functionality
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where('email', 'like', "%{$search}%");
        }

        // Filter by share
        if ($request->has('share_id')) {
            $query->where('share_id', $request->get('share_id'));
        }

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->get('user_id'));
        }

        // Filter by email
        if ($request->has('email')) {
            $query->where('email', 'like', '%' . $request->get('email') . '%');
        }

        // Filter by permission
        if ($request->has('permission')) {
            $query->where('permission', $request->get('permission'));
        }

        // Filter by accessed status
        if ($request->has('accessed')) {
            if ($request->boolean('accessed')) {
                $query->whereNotNull('accessed_at');
            } else {
                $query->whereNull('accessed_at');
            }
        }

        // Order by creation date (newest first)
        $query->orderBy('created_at', 'desc');

        // Pagination
        $perPage = $request->get('per_page', 15);
        $accesses = $query->paginate($perPage);

        return response()->json($accesses);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'share_id' => 'required|exists:shares,id',
            'user_id' => 'nullable|exists:users,id',
            'email' => 'required_without:user_id|email|max:255',
            'permission' => 'required|in:view,download,edit',
        ]);

        // Ensure either user_id or email is provided
        if (empty($validated['user_id']) && empty($validated['email'])) {
            return response()->json([
                'message' => 'Either user_id or email must be provided'
            ], 400);
        }

        // Check if access already exists for this share and user/email
        $existingAccess = ShareAccess::where('share_id', $validated['share_id'])
            ->where(function ($q) use ($validated) {
                if (!empty($validated['user_id'])) {
                    $q->where('user_id', $validated['user_id']);
                }
                if (!empty($validated['email'])) {
                    $q->orWhere('email', $validated['email']);
                }
            })
            ->first();

        if ($existingAccess) {
            return response()->json(['message' => 'Access already exists for this share'], 409);
        }

        $access = ShareAccess::create($validated);

        return response()->json($access->load(['share', 'user']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(ShareAccess $shareAccess): JsonResponse
    {
        return response()->json($shareAccess->load(['share', 'user']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ShareAccess $shareAccess): JsonResponse
    {
        $validated = $request->validate([
            'permission' => 'sometimes|in:view,download,edit',
        ]);

        $shareAccess->update($validated);

        return response()->json($shareAccess->load(['share', 'user']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ShareAccess $shareAccess): JsonResponse
    {
        $shareAccess->delete();

        return response()->json(['message' => 'Share access deleted successfully']);
    }

    /**
     * Mark access as used.
     */
    public function markAccessed(ShareAccess $shareAccess): JsonResponse
    {
        $shareAccess->markAsAccessed();

        return response()->json($shareAccess->load(['share', 'user']));
    }
}