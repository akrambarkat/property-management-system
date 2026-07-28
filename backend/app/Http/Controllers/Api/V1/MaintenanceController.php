<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = MaintenanceRequest::with(['unit.building', 'requestedBy', 'assignedTo']);
        if ($request->status) $query->where('status', $request->status);
        if ($request->priority) $query->where('priority', $request->priority);
        if ($request->unit_id) $query->where('unit_id', $request->unit_id);
        $requests = $query->latest()->get();
        return response()->json(['success' => true, 'data' => $requests]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'unit_id' => 'required|exists:units,id',
            'description' => 'required|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'notes' => 'nullable|string',
        ]);
        $validated['requested_by'] = auth()->id();
        $validated['status'] = 'pending';
        $req = MaintenanceRequest::create($validated);
        $req->load('unit.building');
        return response()->json(['success' => true, 'message' => 'تم إضافة طلب الصيانة', 'data' => $req], 201);
    }

    public function show(MaintenanceRequest $maintenanceRequest): JsonResponse
    {
        $maintenanceRequest->load(['unit.building', 'requestedBy', 'assignedTo']);
        return response()->json(['success' => true, 'data' => $maintenanceRequest]);
    }

    public function update(Request $request, MaintenanceRequest $maintenanceRequest): JsonResponse
    {
        $validated = $request->validate([
            'description' => 'required|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'status' => 'required|in:pending,in_progress,completed,cancelled',
            'assigned_to' => 'nullable|exists:users,id',
            'cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);
        if ($validated['status'] === 'completed') {
            $validated['completed_at'] = now();
        }
        $maintenanceRequest->update($validated);
        return response()->json(['success' => true, 'message' => 'تم تحديث طلب الصيانة', 'data' => $maintenanceRequest]);
    }

    public function updateStatus(Request $request, MaintenanceRequest $maintenanceRequest): JsonResponse
    {
        $validated = $request->validate(['status' => 'required|in:pending,in_progress,completed,cancelled']);
        if ($validated['status'] === 'completed') {
            $validated['completed_at'] = now();
        }
        $maintenanceRequest->update($validated);
        return response()->json(['success' => true, 'message' => 'تم تحديث الحالة']);
    }
}
