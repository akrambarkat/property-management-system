<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Unit;
use App\Services\ContractService;
use App\Services\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContractController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Contract::with(['unit.building.location', 'tenant']);
        if ($request->status) $query->where('status', $request->status);
        if ($request->unit_id) $query->where('unit_id', $request->unit_id);
        if ($request->tenant_id) $query->where('tenant_id', $request->tenant_id);
        $contracts = $query->latest()->get();
        return response()->json(['success' => true, 'data' => $contracts]);
    }

    public function store(Request $request, ContractService $contractService, InvoiceService $invoiceService): JsonResponse
    {
        $validated = $request->validate([
            'unit_id' => 'required|exists:units,id',
            'tenant_id' => 'required|exists:tenants,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'rent_amount' => 'required|numeric|min:0',
            'contract_type' => 'required|in:monthly,yearly',
            'electricity_amount' => 'nullable|numeric|min:0',
            'water_amount' => 'nullable|numeric|min:0',
            'internet_amount' => 'nullable|numeric|min:0',
            'services_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $validated['contract_number'] = $contractService->generateContractNumber();
        $validated['status'] = 'active';
        $contract = Contract::create($validated);

        Unit::where('id', $validated['unit_id'])->update(['status' => 'occupied']);

        // Create rent invoice
        $invoiceData = [
            'contract_id' => $contract->id,
            'issue_date' => $validated['start_date'],
            'due_date' => date('Y-m-d', strtotime($validated['start_date'] . ' +1 month')),
            'rent_amount' => $validated['rent_amount'],
            'electricity_amount' => 0,
            'water_amount' => 0,
            'internet_amount' => 0,
            'services_amount' => 0,
        ];
        $invoiceData['invoice_number'] = $invoiceService->generateInvoiceNumber();
        $invoiceData['total_amount'] = $invoiceService->calculateTotal($invoiceData);
        $invoiceData['status'] = 'unpaid';
        $invoiceData['paid_amount'] = 0;
        Invoice::create($invoiceData);

        // Create service invoice if any service amount > 0
        $serviceAmounts = [
            $validated['electricity_amount'] ?? 0,
            $validated['water_amount'] ?? 0,
            $validated['internet_amount'] ?? 0,
            $validated['services_amount'] ?? 0,
        ];
        if (array_sum($serviceAmounts) > 0) {
            $serviceInvoiceData = [
                'contract_id' => $contract->id,
                'issue_date' => $validated['start_date'],
                'due_date' => date('Y-m-d', strtotime($validated['start_date'] . ' +1 month')),
                'rent_amount' => 0,
                'electricity_amount' => $validated['electricity_amount'] ?? 0,
                'water_amount' => $validated['water_amount'] ?? 0,
                'internet_amount' => $validated['internet_amount'] ?? 0,
                'services_amount' => $validated['services_amount'] ?? 0,
            ];
            $serviceInvoiceData['invoice_number'] = $invoiceService->generateInvoiceNumber();
            $serviceInvoiceData['total_amount'] = $invoiceService->calculateTotal($serviceInvoiceData);
            $serviceInvoiceData['status'] = 'unpaid';
            $serviceInvoiceData['paid_amount'] = 0;
            Invoice::create($serviceInvoiceData);
        }

        $contract->load(['unit.building', 'tenant']);
        return response()->json(['success' => true, 'message' => 'تم إضافة العقد مع الفواتير', 'data' => $contract], 201);
    }

    public function show(Contract $contract): JsonResponse
    {
        $contract->load(['unit.building', 'tenant', 'invoices']);
        return response()->json(['success' => true, 'data' => $contract]);
    }

    public function update(Request $request, Contract $contract): JsonResponse
    {
        $validated = $request->validate([
            'unit_id' => 'required|exists:units,id',
            'tenant_id' => 'required|exists:tenants,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'rent_amount' => 'required|numeric|min:0',
            'contract_type' => 'required|in:monthly,yearly',
            'status' => 'in:active,expired,terminated,renewed',
            'notes' => 'nullable|string',
        ]);
        $contract->update($validated);
        $contract->load(['unit.building', 'tenant']);
        return response()->json(['success' => true, 'message' => 'تم تحديث العقد', 'data' => $contract]);
    }

    public function destroy(Contract $contract): JsonResponse
    {
        $oldUnitId = $contract->unit_id;
        $contract->delete();
        Unit::where('id', $oldUnitId)->update(['status' => 'available']);
        return response()->json(['success' => true, 'message' => 'تم حذف العقد']);
    }

    public function terminate(Contract $contract): JsonResponse
    {
        $contract->update(['status' => 'terminated']);
        Unit::where('id', $contract->unit_id)->update(['status' => 'available']);
        return response()->json(['success' => true, 'message' => 'تم إنهاء العقد', 'data' => $contract]);
    }

    public function expiring(Request $request): JsonResponse
    {
        $days = $request->days ?? 30;
        $contracts = Contract::with(['unit.building', 'tenant'])
            ->where('status', 'active')
            ->where('end_date', '<=', now()->addDays($days))
            ->get();
        return response()->json(['success' => true, 'data' => $contracts]);
    }
}
