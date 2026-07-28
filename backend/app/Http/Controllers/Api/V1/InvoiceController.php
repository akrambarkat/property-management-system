<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Invoice::with(['contract.tenant', 'contract.unit.building']);
        if ($request->status) $query->where('status', $request->status);
        if ($request->contract_id) $query->where('contract_id', $request->contract_id);
        if ($request->from) $query->where('issue_date', '>=', $request->from);
        if ($request->to) $query->where('issue_date', '<=', $request->to);
        $invoices = $query->latest()->get();
        return response()->json(['success' => true, 'data' => $invoices]);
    }

    public function store(Request $request, InvoiceService $invoiceService): JsonResponse
    {
        $validated = $request->validate([
            'contract_id' => 'required|exists:contracts,id',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'rent_amount' => 'numeric|min:0',
            'electricity_amount' => 'numeric|min:0',
            'water_amount' => 'numeric|min:0',
            'internet_amount' => 'numeric|min:0',
            'services_amount' => 'numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $validated['invoice_number'] = $invoiceService->generateInvoiceNumber();
        $validated['total_amount'] = $invoiceService->calculateTotal($validated);
        $validated['status'] = 'unpaid';
        $validated['paid_amount'] = 0;

        $invoice = Invoice::create($validated);
        $invoice->load('contract.tenant');
        return response()->json(['success' => true, 'message' => 'تم إضافة الفاتورة', 'data' => $invoice], 201);
    }

    public function show(Invoice $invoice): JsonResponse
    {
        $invoice->load(['contract.tenant', 'contract.unit.building', 'payments']);
        return response()->json(['success' => true, 'data' => $invoice]);
    }

    public function update(Request $request, Invoice $invoice, InvoiceService $invoiceService): JsonResponse
    {
        $validated = $request->validate([
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'rent_amount' => 'numeric|min:0',
            'electricity_amount' => 'numeric|min:0',
            'water_amount' => 'numeric|min:0',
            'internet_amount' => 'numeric|min:0',
            'services_amount' => 'numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $validated['total_amount'] = $invoiceService->calculateTotal($validated);
        $invoice->update($validated);
        return response()->json(['success' => true, 'message' => 'تم تحديث الفاتورة', 'data' => $invoice]);
    }

    public function destroy(Invoice $invoice): JsonResponse
    {
        $invoice->delete();
        return response()->json(['success' => true, 'message' => 'تم حذف الفاتورة']);
    }

    public function pay(Invoice $invoice, InvoiceService $invoiceService): JsonResponse
    {
        $balance = $invoice->total_amount - $invoice->paid_amount;
        $invoice->update(['paid_amount' => $invoice->total_amount]);
        $invoiceService->updateStatus($invoice);
        return response()->json(['success' => true, 'message' => 'تم تسديد الفاتورة', 'data' => $invoice]);
    }
}
