<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Expense::with(['building', 'createdBy']);
        if ($request->building_id) $query->where('building_id', $request->building_id);
        if ($request->category) $query->where('category', $request->category);
        if ($request->from) $query->where('expense_date', '>=', $request->from);
        if ($request->to) $query->where('expense_date', '<=', $request->to);
        $expenses = $query->latest()->get();
        return response()->json(['success' => true, 'data' => $expenses]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'building_id' => 'required|exists:buildings,id',
            'category' => 'required|in:maintenance,plumbing,electrical,cleaning,security,general',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'description' => 'nullable|string',
        ]);
        $validated['created_by'] = auth()->id();
        $expense = Expense::create($validated);
        $expense->load('building');
        return response()->json(['success' => true, 'message' => 'تم إضافة المصروف', 'data' => $expense], 201);
    }

    public function show(Expense $expense): JsonResponse
    {
        $expense->load(['building', 'createdBy']);
        return response()->json(['success' => true, 'data' => $expense]);
    }

    public function update(Request $request, Expense $expense): JsonResponse
    {
        $validated = $request->validate([
            'building_id' => 'required|exists:buildings,id',
            'category' => 'required|in:maintenance,plumbing,electrical,cleaning,security,general',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'description' => 'nullable|string',
        ]);
        $expense->update($validated);
        return response()->json(['success' => true, 'message' => 'تم تحديث المصروف', 'data' => $expense]);
    }

    public function destroy(Expense $expense): JsonResponse
    {
        $expense->delete();
        return response()->json(['success' => true, 'message' => 'تم حذف المصروف']);
    }
}
