<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TenantController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Tenant::query();
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('first_name', 'like', "%{$request->search}%")
                  ->orWhere('last_name', 'like', "%{$request->search}%")
                  ->orWhere('id_number', 'like', "%{$request->search}%")
                  ->orWhere('phone', 'like', "%{$request->search}%");
            });
        }
        $tenants = $query->with('currentUnit')->latest()->get();
        return response()->json(['success' => true, 'data' => $tenants]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:191',
            'last_name' => 'required|string|max:191',
            'id_number' => 'required|string|max:50|unique:tenants,id_number',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:191',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
            'id_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
        if ($request->hasFile('id_photo')) {
            $validated['id_photo_path'] = $request->file('id_photo')->store('tenants', 'public');
        }
        $validated['is_active'] = true;
        $tenant = Tenant::create($validated);
        return response()->json(['success' => true, 'message' => 'تم إضافة المستأجر', 'data' => $tenant], 201);
    }

    public function show(Tenant $tenant): JsonResponse
    {
        $tenant->load([
            'contracts.unit.building',
            'contracts.invoices.payments',
        ]);
        return response()->json(['success' => true, 'data' => $tenant]);
    }

    public function update(Request $request, Tenant $tenant): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:191',
            'last_name' => 'required|string|max:191',
            'id_number' => 'required|string|max:50|unique:tenants,id_number,' . $tenant->id,
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:191',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
            'id_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
        if ($request->hasFile('id_photo')) {
            if ($tenant->id_photo_path) {
                Storage::disk('public')->delete($tenant->id_photo_path);
            }
            $validated['id_photo_path'] = $request->file('id_photo')->store('tenants', 'public');
        }
        $tenant->update($validated);
        $tenant->load('contracts.unit.building');
        return response()->json(['success' => true, 'message' => 'تم تحديث المستأجر', 'data' => $tenant]);
    }

    public function destroy(Tenant $tenant): JsonResponse
    {
        $tenant->delete();
        return response()->json(['success' => true, 'message' => 'تم حذف المستأجر']);
    }
}
