<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\UtilityReading;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UtilityReadingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = UtilityReading::with(['unit.building', 'recordedBy']);
        if ($request->unit_id) $query->where('unit_id', $request->unit_id);
        if ($request->type) $query->where('utility_type', $request->type);
        if ($request->from) $query->where('reading_date', '>=', $request->from);
        if ($request->to) $query->where('reading_date', '<=', $request->to);
        $readings = $query->latest()->get();
        return response()->json(['success' => true, 'data' => $readings]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'unit_id' => 'required|exists:units,id',
            'reading_date' => 'required|date',
            'utility_type' => 'required|in:electricity,water',
            'previous_reading' => 'required|numeric|min:0',
            'current_reading' => 'required|numeric|min:0|gte:previous_reading',
        ]);

        $validated['consumption'] = $validated['current_reading'] - $validated['previous_reading'];

        $priceKey = $validated['utility_type'] === 'electricity' ? 'electricity_unit_price' : 'water_unit_price';
        $validated['unit_price'] = Setting::where('key', $priceKey)->value('value') ?? 0;
        $validated['total'] = round($validated['consumption'] * $validated['unit_price'], 2);
        $validated['recorded_by'] = auth()->id();

        $reading = UtilityReading::create($validated);
        $reading->load(['unit.building', 'recordedBy']);
        return response()->json(['success' => true, 'message' => 'تم تسجيل القراءة', 'data' => $reading], 201);
    }

    public function show(UtilityReading $utilityReading): JsonResponse
    {
        $utilityReading->load(['unit.building', 'recordedBy']);
        return response()->json(['success' => true, 'data' => $utilityReading]);
    }

    public function update(Request $request, UtilityReading $utilityReading): JsonResponse
    {
        $validated = $request->validate([
            'reading_date' => 'required|date',
            'previous_reading' => 'required|numeric|min:0',
            'current_reading' => 'required|numeric|min:0|gte:previous_reading',
            'unit_price' => 'numeric|min:0',
        ]);

        $validated['consumption'] = $validated['current_reading'] - $validated['previous_reading'];
        $validated['total'] = round($validated['consumption'] * ($validated['unit_price'] ?? $utilityReading->unit_price), 2);
        $utilityReading->update($validated);
        return response()->json(['success' => true, 'message' => 'تم تحديث القراءة', 'data' => $utilityReading]);
    }

    public function destroy(UtilityReading $utilityReading): JsonResponse
    {
        $utilityReading->delete();
        return response()->json(['success' => true, 'message' => 'تم حذف القراءة']);
    }
}
