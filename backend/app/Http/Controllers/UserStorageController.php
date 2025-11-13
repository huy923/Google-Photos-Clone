<?php

namespace App\Http\Controllers;

use App\Models\UserStorage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserStorageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = UserStorage::with('user');

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->get('user_id'));
        }

        // Filter by storage usage range
        if ($request->has('min_used_storage')) {
            $query->where('used_storage', '>=', $request->get('min_used_storage'));
        }
        if ($request->has('max_used_storage')) {
            $query->where('used_storage', '<=', $request->get('max_used_storage'));
        }

        // Filter by file count range
        if ($request->has('min_file_count')) {
            $query->where('file_count', '>=', $request->get('min_file_count'));
        }
        if ($request->has('max_file_count')) {
            $query->where('file_count', '<=', $request->get('max_file_count'));
        }

        // Filter by storage full status
        if ($request->has('is_full')) {
            if ($request->boolean('is_full')) {
                $query->whereRaw('used_storage >= max_storage');
            } else {
                $query->whereRaw('used_storage < max_storage');
            }
        }

        // Order by used storage (highest first)
        $query->orderBy('used_storage', 'desc');

        // Pagination
        $perPage = $request->get('per_page', 15);
        $storages = $query->paginate($perPage);

        return response()->json($storages);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id|unique:user_storage,user_id',
            'used_storage' => 'required|integer|min:0',
            'max_storage' => 'required|integer|min:0',
            'file_count' => 'required|integer|min:0',
        ]);

        // Ensure used_storage doesn't exceed max_storage
        if ($validated['used_storage'] > $validated['max_storage']) {
            return response()->json([
                'message' => 'Used storage cannot exceed max storage'
            ], 400);
        }

        $storage = UserStorage::create($validated);

        return response()->json($storage->load('user'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(UserStorage $userStorage): JsonResponse
    {
        return response()->json($userStorage->load('user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, UserStorage $userStorage): JsonResponse
    {
        $validated = $request->validate([
            'used_storage' => 'sometimes|integer|min:0',
            'max_storage' => 'sometimes|integer|min:0',
            'file_count' => 'sometimes|integer|min:0',
        ]);

        // Ensure used_storage doesn't exceed max_storage
        $usedStorage = $validated['used_storage'] ?? $userStorage->used_storage;
        $maxStorage = $validated['max_storage'] ?? $userStorage->max_storage;

        if ($usedStorage > $maxStorage) {
            return response()->json([
                'message' => 'Used storage cannot exceed max storage'
            ], 400);
        }

        $userStorage->update($validated);

        return response()->json($userStorage->load('user'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserStorage $userStorage): JsonResponse
    {
        $userStorage->delete();

        return response()->json(['message' => 'User storage deleted successfully']);
    }

    /**
     * Add storage usage.
     */
    public function addStorage(Request $request, UserStorage $userStorage): JsonResponse
    {
        $validated = $request->validate([
            'bytes' => 'required|integer|min:1',
        ]);

        if (!$userStorage->hasEnoughStorage($validated['bytes'])) {
            return response()->json([
                'message' => 'Not enough storage space available'
            ], 400);
        }

        $userStorage->addStorage($validated['bytes']);

        return response()->json($userStorage->load('user'));
    }

    /**
     * Remove storage usage.
     */
    public function removeStorage(Request $request, UserStorage $userStorage): JsonResponse
    {
        $validated = $request->validate([
            'bytes' => 'required|integer|min:1',
        ]);

        if ($validated['bytes'] > $userStorage->used_storage) {
            return response()->json([
                'message' => 'Cannot remove more storage than currently used'
            ], 400);
        }

        $userStorage->removeStorage($validated['bytes']);

        return response()->json($userStorage->load('user'));
    }

    /**
     * Get storage statistics.
     */
    public function statistics(): JsonResponse
    {
        $totalUsers = UserStorage::count();
        $totalUsedStorage = UserStorage::sum('used_storage');
        $totalMaxStorage = UserStorage::sum('max_storage');
        $totalFiles = UserStorage::sum('file_count');
        $fullStorages = UserStorage::whereRaw('used_storage >= max_storage')->count();

        return response()->json([
            'total_users' => $totalUsers,
            'total_used_storage' => $totalUsedStorage,
            'total_max_storage' => $totalMaxStorage,
            'total_files' => $totalFiles,
            'full_storages' => $fullStorages,
            'usage_percentage' => $totalMaxStorage > 0 ? ($totalUsedStorage / $totalMaxStorage) * 100 : 0,
        ]);
    }
}