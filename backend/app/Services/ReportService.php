<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Unit;
use Carbon\Carbon;

class ReportService
{
    public function getDashboardStats(): array
    {
        return [
            'total_units' => Unit::count(),
            'occupied_units' => Unit::where('status', 'occupied')->count(),
            'available_units' => Unit::where('status', 'available')->count(),
            'maintenance_units' => Unit::where('status', 'maintenance')->count(),
            'monthly_income' => Invoice::whereMonth('issue_date', now()->month)
                ->whereYear('issue_date', now()->year)->sum('paid_amount'),
            'overdue_amount' => Invoice::whereIn('status', ['unpaid', 'overdue'])->sum('total_amount'),
            'occupancy_rate' => Unit::count() > 0
                ? round((Unit::where('status', 'occupied')->count() / Unit::count()) * 100, 1)
                : 0,
            'active_contracts' => Contract::where('status', 'active')->count(),
            'recent_payments' => Payment::with('invoice.contract.tenant')
                ->latest()->take(5)->get()->map(fn($p) => [
                    'receipt_number' => $p->receipt_number,
                    'tenant' => ($p->invoice?->contract?->tenant?->first_name ?? '') . ' ' . ($p->invoice?->contract?->tenant?->last_name ?? ''),
                    'amount' => $p->amount,
                    'payment_date' => $p->payment_date?->format('Y-m-d'),
                ]),
            'overdue_invoices' => Invoice::whereIn('status', ['unpaid', 'overdue'])
                ->where('due_date', '<', now())->with('contract.tenant')
                ->latest()->take(5)->get()->map(fn($inv) => [
                    'invoice_number' => $inv->invoice_number,
                    'tenant' => ($inv->contract?->tenant?->first_name ?? '') . ' ' . ($inv->contract?->tenant?->last_name ?? ''),
                    'total_amount' => $inv->total_amount,
                    'due_date' => $inv->due_date?->format('Y-m-d'),
                ]),
        ];
    }

    public function getProfitLoss(?int $buildingId = null, ?string $from = null, ?string $to = null): array
    {
        $incomeQuery = Invoice::query();
        $expenseQuery = Expense::query();

        if ($buildingId) {
            $incomeQuery->whereHas('contract.unit.building', fn($q) => $q->where('id', $buildingId));
            $expenseQuery->where('building_id', $buildingId);
        }
        if ($from) {
            $incomeQuery->where('issue_date', '>=', $from);
            $expenseQuery->where('expense_date', '>=', $from);
        }
        if ($to) {
            $incomeQuery->where('issue_date', '<=', $to);
            $expenseQuery->where('expense_date', '<=', $to);
        }

        $totalRent = (clone $incomeQuery)->sum('rent_amount');
        $totalUtilities = (clone $incomeQuery)->sum('electricity_amount') +
            (clone $incomeQuery)->sum('water_amount') +
            (clone $incomeQuery)->sum('internet_amount');
        $totalIncome = (clone $incomeQuery)->sum('paid_amount');
        $totalExpenses = (clone $expenseQuery)->sum('amount');

        $expensesByCategory = $expenseQuery->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')->pluck('total', 'category')->toArray();

        return [
            'total_rent' => $totalRent,
            'total_utilities' => $totalUtilities,
            'total_income' => $totalIncome,
            'expenses_by_category' => $expensesByCategory,
            'total_expenses' => $totalExpenses,
            'net_profit' => $totalIncome - $totalExpenses,
            'details' => $this->getIncomeDetails($buildingId, $from, $to),
        ];
    }

    private function getIncomeDetails(?int $buildingId, ?string $from, ?string $to): array
    {
        $query = Invoice::with(['contract.unit.building', 'contract.tenant']);
        if ($buildingId) {
            $query->whereHas('contract.unit.building', fn($q) => $q->where('id', $buildingId));
        }
        if ($from) $query->where('issue_date', '>=', $from);
        if ($to) $query->where('issue_date', '<=', $to);

        return $query->limit(50)->get()->map(fn($inv) => [
            'building' => $inv->contract?->unit?->building?->name ?? '',
            'unit' => $inv->contract?->unit?->unit_number ?? '',
            'tenant' => ($inv->contract?->tenant?->first_name ?? '') . ' ' . ($inv->contract?->tenant?->last_name ?? ''),
            'rent' => $inv->rent_amount,
            'utilities' => $inv->electricity_amount + $inv->water_amount + $inv->internet_amount,
            'total' => $inv->total_amount,
        ])->toArray();
    }
}
