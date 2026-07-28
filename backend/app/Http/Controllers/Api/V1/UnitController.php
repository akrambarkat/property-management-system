<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Unit::with('building.location');
        if ($request->building_id) $query->where('building_id', $request->building_id);
        if ($request->status) $query->where('status', $request->status);
        if ($request->unit_type) $query->where('unit_type', $request->unit_type);
        $units = $query->latest()->get();
        return response()->json(['success' => true, 'data' => $units]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'building_id' => 'required|exists:buildings,id',
            'unit_number' => 'required|string|max:50',
            'unit_type' => 'required|in:apartment,shop,warehouse',
            'floor' => 'integer|min:0',
            'area' => 'nullable|numeric|min:0',
            'rent_amount' => 'numeric|min:0',
            'status' => 'required|in:available,occupied,maintenance',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        $unit = Unit::create($validated);
        $unit->load('building');
        return response()->json(['success' => true, 'message' => 'تم إضافة الوحدة', 'data' => $unit], 201);
    }

    public function show(Unit $unit): JsonResponse
    {
        $unit->load('building.location', 'currentContract.tenant');
        return response()->json(['success' => true, 'data' => $unit]);
    }

    public function update(Request $request, Unit $unit): JsonResponse
    {
        $validated = $request->validate([
            'building_id' => 'required|exists:buildings,id',
            'unit_number' => 'required|string|max:50',
            'unit_type' => 'required|in:apartment,shop,warehouse',
            'floor' => 'integer|min:0',
            'area' => 'nullable|numeric|min:0',
            'rent_amount' => 'numeric|min:0',
            'status' => 'required|in:available,occupied,maintenance',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        $unit->update($validated);
        $unit->load('building');
        return response()->json(['success' => true, 'message' => 'تم تحديث الوحدة', 'data' => $unit]);
    }

    public function destroy(Unit $unit): JsonResponse
    {
        $unit->delete();
        return response()->json(['success' => true, 'message' => 'تم حذف الوحدة']);
    }

    public function updateStatus(Request $request, Unit $unit): JsonResponse
    {
        $validated = $request->validate(['status' => 'required|in:available,occupied,maintenance']);
        $unit->update($validated);
        return response()->json(['success' => true, 'message' => 'تم تحديث الحالة', 'data' => $unit]);
    }
}
