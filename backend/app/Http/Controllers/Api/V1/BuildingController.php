<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Building;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BuildingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Building::with('location')->withCount('units');
        if ($request->location_id) $query->where('location_id', $request->location_id);
        $buildings = $query->latest()->get();
        return response()->json(['success' => true, 'data' => $buildings]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'location_id' => 'required|exists:locations,id',
            'name' => 'required|string|max:191',
            'address' => 'nullable|string',
            'floors' => 'integer|min:1',
            'is_active' => 'boolean',
        ]);
        $building = Building::create($validated);
        $building->load('location');
        return response()->json(['success' => true, 'message' => 'تم إضافة المبنى', 'data' => $building], 201);
    }

    public function show(Building $building): JsonResponse
    {
        $building->load('location', 'units');
        return response()->json(['success' => true, 'data' => $building]);
    }

    public function update(Request $request, Building $building): JsonResponse
    {
        $validated = $request->validate([
            'location_id' => 'required|exists:locations,id',
            'name' => 'required|string|max:191',
            'address' => 'nullable|string',
            'floors' => 'integer|min:1',
            'is_active' => 'boolean',
        ]);
        $building->update($validated);
        $building->load('location');
        return response()->json(['success' => true, 'message' => 'تم تحديث المبنى', 'data' => $building]);
    }

    public function destroy(Building $building): JsonResponse
    {
        $building->delete();
        return response()->json(['success' => true, 'message' => 'تم حذف المبنى']);
    }
}
