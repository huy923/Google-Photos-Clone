<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Notification::with('user');

        // Search functionality
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%");
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

        // Filter by read status
        if ($request->has('is_read')) {
            $query->where('is_read', $request->boolean('is_read'));
        }

        // Filter by unread only
        if ($request->has('unread_only') && $request->boolean('unread_only')) {
            $query->unread();
        }

        // Order by creation date (newest first)
        $query->orderBy('created_at', 'desc');

        // Pagination
        $perPage = $request->get('per_page', 15);
        $notifications = $query->paginate($perPage);

        return response()->json($notifications);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => 'required|string|max:50',
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:1000',
            'data' => 'nullable|array',
            'is_read' => 'boolean',
        ]);

        $notification = Notification::create($validated);

        return response()->json($notification->load('user'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Notification $notification): JsonResponse
    {
        return response()->json($notification->load('user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Notification $notification): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'sometimes|string|max:50',
            'title' => 'sometimes|string|max:255',
            'message' => 'sometimes|string|max:1000',
            'data' => 'sometimes|array',
            'is_read' => 'sometimes|boolean',
        ]);

        $notification->update($validated);

        return response()->json($notification->load('user'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Notification $notification): JsonResponse
    {
        $notification->delete();

        return response()->json(['message' => 'Notification deleted successfully']);
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(Notification $notification): JsonResponse
    {
        $notification->markAsRead();

        return response()->json($notification->load('user'));
    }

    /**
     * Mark notification as unread.
     */
    public function markAsUnread(Notification $notification): JsonResponse
    {
        $notification->markAsUnread();

        return response()->json($notification->load('user'));
    }

    /**
     * Mark all notifications as read for a user.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $count = Notification::where('user_id', $validated['user_id'])
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json([
            'message' => "Marked {$count} notifications as read"
        ]);
    }

    /**
     * Get unread notification count for a user.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $count = Notification::where('user_id', $validated['user_id'])
            ->unread()
            ->count();

        return response()->json(['unread_count' => $count]);
    }
}