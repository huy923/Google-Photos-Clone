<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Profile::with('user');

        // Search functionality
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('bio', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->get('user_id'));
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $profiles = $query->paginate($perPage);

        return response()->json($profiles);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id|unique:profiles,user_id',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:1000',
            'phone' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date',
            'location' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'preferences' => 'nullable|array',
        ]);

        $profile = Profile::create($validated);

        return response()->json($profile->load('user'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Profile $profile): JsonResponse
    {
        return response()->json($profile->load('user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Profile $profile): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'bio' => 'sometimes|string|max:1000',
            'phone' => 'sometimes|string|max:20',
            'birth_date' => 'sometimes|date',
            'location' => 'sometimes|string|max:255',
            'website' => 'sometimes|url|max:255',
            'preferences' => 'sometimes|array',
        ]);

        $profile->update($validated);

        return response()->json($profile->load('user'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Profile $profile): JsonResponse
    {
        $profile->delete();

        return response()->json(['message' => 'Profile deleted successfully']);
    }
}