<?php

namespace App\Http\Controllers;

use App\Models\MediaFile;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class MediaFileController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = MediaFile::with(['user', 'metadata', 'tags', 'albums']);

        // Search functionality
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('original_name', 'like', "%{$search}%")
                    ->orWhere('filename', 'like', "%{$search}%");
            });
        }

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->get('user_id'));
        }

        // Filter by file type
        if ($request->has('file_type')) {
            $query->where('file_type', $request->get('file_type'));
        }

        // Filter by mime type
        if ($request->has('mime_type')) {
            $query->where('mime_type', 'like', $request->get('mime_type') . '%');
        }

        // Filter by processed status
        if ($request->has('is_processed')) {
            $query->where('is_processed', $request->boolean('is_processed'));
        }

        // Filter by deleted status
        if ($request->has('is_deleted')) {
            $query->where('is_deleted', $request->boolean('is_deleted'));
        } else {
            // By default, show only non-deleted media files
            $query->notDeleted();
        }

        // Date range filter
        if ($request->has('date_from')) {
            $query->where('created_at', '>=', $request->get('date_from'));
        }
        if ($request->has('date_to')) {
            $query->where('created_at', '<=', $request->get('date_to'));
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $mediaFiles = $query->paginate($perPage);

        return response()->json($mediaFiles);
    }

    /**
     * Store a newly uploaded file.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validate the request
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'file' => 'required|file|max:10485760', // Max 10GB (in KB)
            ]);

            $file = $request->file('file');
            $userId = $request->input('user_id');
            $relativePath = $request->input('file_path', ''); // Get the relative path from the request

            $user = User::findOrFail($userId);

            // Clean up the relative path (remove leading/trailing slashes)
            $relativePath = trim($relativePath, '/');

            // Create the full directory path including user directory and relative path
            $userDirectory = 'uploads/' . $user->username_code;
            $fullDirectory = $relativePath
                ? $userDirectory . '/' . $relativePath
                : $userDirectory;

            // Ensure the directory exists
            if (!Storage::disk('public')->exists($fullDirectory)) {
                Storage::disk('public')->makeDirectory($fullDirectory, 0755, true);
            }

            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs($fullDirectory, $filename, 'public');

            $mimeType = $file->getClientMimeType();
            $fileType = $this->getFileType($mimeType);
            $fileSize = $file->getSize();

            $publicUrl = asset('storage/' . $path);

            $mediaFile = MediaFile::create([
                'user_id' => $userId,
                'original_name' => $file->getClientOriginalName(),
                'filename' => $filename,
                'file_path' => $path,
                'public_url' => $publicUrl,
                'mime_type' => $mimeType,
                'file_type' => $fileType,
                'file_size' => $fileSize,
                'is_processed' => false,
                'is_optimized' => false,
                'is_deleted' => false,
            ]);

            return response()->json([
                'message' => 'File uploaded successfully',
                'data' => $mediaFile
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Upload error: ' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
            return response()->json([
                'message' => 'Upload failed: ' . $e->getMessage(),
                'error' => get_class($e)
            ], 500);
        }
    }

    /**
     * Determine file type based on MIME type
     */
    private function getFileType($mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        } elseif (str_starts_with($mimeType, 'video/')) {
            return 'video';
        } elseif (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        } elseif (str_starts_with($mimeType, 'folder/')) {
            return 'folder';
        } else {
            return 'document';
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(MediaFile $mediaFile): JsonResponse
    {
        return response()->json($mediaFile->load([
            'user',
            'metadata',
            'tags',
            'albums',
            'comments',
            'favorites',
            'views',
            'shares'
        ]));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MediaFile $mediaFile): JsonResponse
    {
        $validated = $request->validate([
            'original_name' => 'sometimes|string|max:255',
            'filename' => 'sometimes|string|max:255',
            'file_path' => 'sometimes|string|max:500',
            'thumbnail_path' => 'sometimes|string|max:500',
            'mime_type' => 'sometimes|string|max:100',
            'file_type' => 'sometimes|in:image,video,audio,document',
            'file_size' => 'sometimes|integer|min:0',
            'width' => 'sometimes|integer|min:0',
            'height' => 'sometimes|integer|min:0',
            'duration' => 'sometimes|integer|min:0',
            'is_processed' => 'sometimes|boolean',
            'is_optimized' => 'sometimes|boolean',
            'is_deleted' => 'sometimes|boolean',
        ]);

        $mediaFile->update($validated);

        return response()->json($mediaFile->load(['user', 'metadata', 'tags']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MediaFile $mediaFile): JsonResponse
    {
        // Soft delete by setting is_deleted to true
        $mediaFile->update(['is_deleted' => true, 'deleted_at' => now()]);

        return response()->json(['message' => 'Media file deleted successfully']);
    }

    /**
     * Restore a soft-deleted media file.
     */
    public function restore(MediaFile $mediaFile): JsonResponse
    {
        $mediaFile->update(['is_deleted' => false, 'deleted_at' => null]);

        return response()->json($mediaFile->load(['user', 'metadata', 'tags']));
    }
    public function uploadFolder(Request $request)
    {
        $files = $request->file('files');
        $paths = $request->input('paths');

        foreach ($files as $index => $file) {
            $path = $paths[$index] ?? $file->getClientOriginalName();
            $folderPath = dirname($path);

            // Lưu file vào đúng folder cấu trúc
            $storedPath = $file->storeAs("uploads/{$folderPath}", $file->getClientOriginalName(), 'public');

            // Nếu bạn muốn lưu DB
            MediaFile::create([
                'path' => $storedPath,
                'folder' => $folderPath,
            ]);
        }

        return response()->json(['success' => true]);
    }
}
