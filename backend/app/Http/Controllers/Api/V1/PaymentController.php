<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\InvoiceService;
use App\Services\ReceiptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Payment::with(['invoice.contract.tenant', 'createdBy']);
        if ($request->invoice_id) $query->where('invoice_id', $request->invoice_id);
        if ($request->from) $query->where('payment_date', '>=', $request->from);
        if ($request->to) $query->where('payment_date', '<=', $request->to);
        $payments = $query->latest()->get();
        return response()->json(['success' => true, 'data' => $payments]);
    }

    public function store(Request $request, ReceiptService $receiptService, InvoiceService $invoiceService): JsonResponse
    {
        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'reference_number' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $validated['receipt_number'] = $receiptService->generateReceiptNumber();
        $validated['created_by'] = auth()->id();

        $payment = Payment::create($validated);

        $invoice = $payment->invoice;
        $invoice->increment('paid_amount', $validated['amount']);
        $invoiceService->updateStatus($invoice);

        $payment->load(['invoice.contract.tenant', 'createdBy']);
        return response()->json(['success' => true, 'message' => 'تم تسجيل الدفعة', 'data' => $payment], 201);
    }

    public function show(Payment $payment): JsonResponse
    {
        $payment->load(['invoice.contract.tenant', 'createdBy']);
        return response()->json(['success' => true, 'data' => $payment]);
    }

    public function destroy(Payment $payment, InvoiceService $invoiceService): JsonResponse
    {
        $invoice = $payment->invoice;
        $invoice->decrement('paid_amount', $payment->amount);
        $invoiceService->updateStatus($invoice);
        $payment->delete();
        return response()->json(['success' => true, 'message' => 'تم حذف الدفعة']);
    }
}
