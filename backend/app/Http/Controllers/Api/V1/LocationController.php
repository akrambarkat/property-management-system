<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function index(): JsonResponse
    {
        $locations = Location::withCount('buildings')->latest()->get();
        return response()->json(['success' => true, 'data' => $locations]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'address' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        $location = Location::create($validated);
        return response()->json(['success' => true, 'message' => 'تم إضافة الموقع', 'data' => $location], 201);
    }

    public function show(Location $location): JsonResponse
    {
        $location->loadCount('buildings');
        return response()->json(['success' => true, 'data' => $location]);
    }

    public function update(Request $request, Location $location): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'address' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        $location->update($validated);
        return response()->json(['success' => true, 'message' => 'تم تحديث الموقع', 'data' => $location]);
    }

    public function destroy(Location $location): JsonResponse
    {
        $location->delete();
        return response()->json(['success' => true, 'message' => 'تم حذف الموقع']);
    }
}
