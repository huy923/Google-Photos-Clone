<?php

namespace App\Http\Controllers;

use App\Models\MediaComment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MediaCommentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = MediaComment::with(['user', 'mediaFile', 'parent', 'replies']);

        // Search functionality
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where('comment', 'like', "%{$search}%");
        }

        // Filter by media file
        if ($request->has('media_file_id')) {
            $query->where('media_file_id', $request->get('media_file_id'));
        }

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->get('user_id'));
        }

        // Filter by parent comment (top-level comments only)
        if ($request->has('top_level_only') && $request->boolean('top_level_only')) {
            $query->topLevel();
        }

        // Filter by parent comment (replies only)
        if ($request->has('parent_id')) {
            $query->where('parent_id', $request->get('parent_id'));
        }

        // Filter by edited status
        if ($request->has('is_edited')) {
            $query->where('is_edited', $request->boolean('is_edited'));
        }

        // Order by creation date (newest first)
        $query->orderBy('created_at', 'desc');

        // Pagination
        $perPage = $request->get('per_page', 15);
        $comments = $query->paginate($perPage);

        return response()->json($comments);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'media_file_id' => 'required|exists:media_files,id',
            'user_id' => 'required|exists:users,id',
            'comment' => 'required|string|max:1000',
            'parent_id' => 'nullable|exists:media_comments,id',
            'is_edited' => 'boolean',
        ]);

        $comment = MediaComment::create($validated);

        return response()->json($comment->load(['user', 'mediaFile', 'parent']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(MediaComment $mediaComment): JsonResponse
    {
        return response()->json($mediaComment->load([
            'user', 
            'mediaFile', 
            'parent', 
            'replies.user'
        ]));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MediaComment $mediaComment): JsonResponse
    {
        $validated = $request->validate([
            'comment' => 'sometimes|string|max:1000',
            'is_edited' => 'sometimes|boolean',
        ]);

        // Mark as edited if comment content is being updated
        if (isset($validated['comment']) && $validated['comment'] !== $mediaComment->comment) {
            $validated['is_edited'] = true;
        }

        $mediaComment->update($validated);

        return response()->json($mediaComment->load(['user', 'mediaFile', 'parent']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MediaComment $mediaComment): JsonResponse
    {
        // Also delete all replies to this comment
        $mediaComment->replies()->delete();
        $mediaComment->delete();

        return response()->json(['message' => 'Comment and its replies deleted successfully']);
    }
}