<?php

namespace App\Services;

use App\Models\Building;
use App\Models\Contract;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\Unit;
use Carbon\Carbon;

class ReportService
{
    public function getDashboardStats(): array
    {
        $totalUnits = Unit::count();
        $occupiedUnits = Unit::where('status', 'occupied')->count();
        $availableUnits = Unit::where('status', 'available')->count();
        $maintenanceUnits = Unit::where('status', 'maintenance')->count();

        $monthlyIncome = Invoice::whereMonth('issue_date', now()->month)
            ->whereYear('issue_date', now()->year)->sum('paid_amount');

        $monthlyExpenses = Expense::whereMonth('expense_date', now()->month)
            ->whereYear('expense_date', now()->year)->sum('amount');

        $occupancyRate = $totalUnits > 0
            ? round(($occupiedUnits / $totalUnits) * 100, 1)
            : 0;

        $overdueAmount = Invoice::whereIn('status', ['unpaid', 'overdue'])->sum('total_amount');

        $totalPaid = Invoice::sum('paid_amount');
        $totalInvoiced = Invoice::sum('total_amount');
        $collectionRate = $totalInvoiced > 0
            ? round(($totalPaid / $totalInvoiced) * 100, 1)
            : 0;

        $totalBuildings = Building::count();

        $arabicMonths = [
            1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
            5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
            9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
        ];

        $monthlyCashFlow = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $income = (float) Invoice::whereMonth('issue_date', $date->month)
                ->whereYear('issue_date', $date->year)->sum('paid_amount');
            $expenses = (float) Expense::whereMonth('expense_date', $date->month)
                ->whereYear('expense_date', $date->year)->sum('amount');
            $monthlyCashFlow[] = [
                'month' => $date->format('Y-m'),
                'label' => $arabicMonths[(int) $date->format('n')],
                'income' => $income,
                'expenses' => $expenses,
                'net_profit' => $income - $expenses,
            ];
        }

        $latePayments = Invoice::whereIn('status', ['unpaid', 'overdue'])
            ->where('due_date', '<', now())
            ->with('contract.tenant', 'contract.unit')
            ->latest('due_date')
            ->take(10)
            ->get()
            ->map(fn($inv) => [
                'id' => $inv->id,
                'tenant' => ($inv->contract?->tenant?->first_name ?? '') . ' ' . ($inv->contract?->tenant?->last_name ?? ''),
                'unit' => $inv->contract?->unit?->unit_number ?? '',
                'daysLate' => now()->startOfDay()->diffInDays($inv->due_date),
                'amount' => (float) $inv->total_amount,
            ]);

        $maintenanceRequests = MaintenanceRequest::whereIn('status', ['pending', 'in_progress'])
            ->with('unit.building', 'assignedTo')
            ->latest()
            ->take(10)
            ->get()
            ->map(fn($r) => [
                'id' => $r->id,
                'title' => $r->description,
                'building' => $r->unit?->building?->name ?? '',
                'technician' => $r->assignedTo?->name ?? 'غير محدد',
                'priority' => $r->priority === 'urgent' ? 'عالي جداً' : ($r->priority === 'high' ? 'عالي' : ($r->priority === 'medium' ? 'متوسط' : 'منخفض')),
                'priorityClass' => in_array($r->priority, ['urgent', 'high']) ? 'p-danger' : 'p-warning',
            ]);

        $upcomingRenewals = Contract::where('status', 'active')
            ->whereBetween('end_date', [now(), now()->addDays(30)])
            ->with('tenant')
            ->latest('end_date')
            ->take(10)
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'tenant' => ($c->tenant?->first_name ?? '') . ' ' . ($c->tenant?->last_name ?? ''),
                'contractNumber' => $c->contract_number,
                'expiryDate' => $c->end_date?->format('Y-m-d'),
            ]);

        $openMaintenanceCount = MaintenanceRequest::whereIn('status', ['pending', 'in_progress'])->count();
        $urgentMaintenanceCount = MaintenanceRequest::whereIn('status', ['pending', 'in_progress'])
            ->where('priority', 'urgent')->count();

        return [
            'total_locations' => Location::count(),
            'total_buildings' => $totalBuildings,
            'total_units' => $totalUnits,
            'occupied_units' => $occupiedUnits,
            'available_units' => $availableUnits,
            'vacant_units' => $availableUnits,
            'maintenance_units' => $maintenanceUnits,
            'monthly_income' => $monthlyIncome,
            'monthly_expenses' => $monthlyExpenses,
            'net_profit' => $monthlyIncome - $monthlyExpenses,
            'overdue_amount' => $overdueAmount,
            'outstanding_amount' => $overdueAmount,
            'occupancy_rate' => $occupancyRate,
            'collection_rate' => $collectionRate,
            'active_contracts' => Contract::where('status', 'active')->count(),
            'monthly_cash_flow' => $monthlyCashFlow,
            'late_payments' => $latePayments,
            'maintenance_requests' => $maintenanceRequests,
            'upcoming_renewals' => $upcomingRenewals,
            'open_maintenance_count' => $openMaintenanceCount,
            'urgent_maintenance_count' => $urgentMaintenanceCount,
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
