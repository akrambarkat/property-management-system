<?php

namespace App\Services;

use App\Models\Building;
use App\Models\Contract;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Location;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Models\UtilityReading;

class DataExportService
{
    private const LOCATION_ACTIVE = [true => 'نشط', false => 'معطل'];

    private const UNIT_STATUS = ['available' => 'متاحة (شاغرة)', 'occupied' => 'مشغولة (مؤجرة)', 'maintenance' => 'تحت الصيانة'];

    private const UNIT_TYPE = ['apartment' => 'شقة سكنية', 'shop' => 'محل تجاري', 'warehouse' => 'مخزن'];

    private const INVOICE_STATUS = ['unpaid' => 'غير مدفوعة', 'partial' => 'مدفوعة جزئياً', 'paid' => 'مدفوعة بالكامل', 'overdue' => 'متأخرة'];

    private const CONTRACT_STATUS = ['active' => 'نشط (ساري)', 'expired' => 'منتهي', 'terminated' => 'مفسوخ'];

    private const PAYMENT_METHOD = ['cash' => 'نقدي', 'bank_transfer' => 'تحويل بنكي', 'cheque' => 'شيك'];

    private const UTILITY_TYPE = ['electricity' => 'كهرباء', 'water' => 'مياه', 'gas' => 'غاز'];

    private const EXPENSE_CATEGORY = [
        'maintenance' => 'صيانة وتصليحات', 'electricity' => 'كهرباء خدمات', 'water' => 'مياه خدمات',
        'cleaning' => 'نظافة وتدبير', 'security' => 'حراسة وأمن', 'admin' => 'إدارية وعمومية', 'other' => 'أخرى',
    ];

    private const MAINTENANCE_STATUS = ['pending' => 'قيد الانتظار', 'in_progress' => 'قيد التنفيذ', 'completed' => 'مكتملة', 'cancelled' => 'ملغاة'];

    private const MAINTENANCE_PRIORITY = ['low' => 'منخفضة', 'medium' => 'متوسطة', 'high' => 'عالية (طوارئ)', 'urgent' => 'عاجلة'];

    private const ROLE = ['super_admin' => 'مدير النظام', 'employee' => 'موظف إدارة', 'guard' => 'حارس مبنى'];

    /**
     * Load a list of display rows for the given entity/key.
     */
    public function list(string $entity, array $params = [], string $currency = 'ر.س'): array
    {
        return match ($entity) {
            'locations' => $this->locations(),
            'buildings' => $this->buildings($params),
            'units' => $this->units($params, $currency),
            'tenants' => $this->tenants(),
            'contracts' => $this->contracts($currency),
            'invoices' => $this->invoices($params, $currency),
            'payments' => $this->payments($currency),
            'utilities' => $this->utilities($params, $currency),
            'expenses' => $this->expenses($params, $currency),
            'maintenance' => $this->maintenance($params),
            'users' => $this->users($params),
            default => abort(404, 'كيان غير معروف'),
        };
    }

    private function locations(): array
    {
        $rows = Location::withCount('buildings')->orderBy('id')->get()->values()->map(fn ($l, $i) => [
            $i + 1,
            $l->name,
            $l->address ?: '',
            self::LOCATION_ACTIVE[(bool) $l->is_active] ?? '',
            ltrim($l->buildings_count),
        ]);

        return [
            'title' => 'قائمة المواقع',
            'columns' => [['key' => 'id', 'label' => 'الرقم'], ['key' => 'name', 'label' => 'الاسم'], ['key' => 'address', 'label' => 'العنوان'], ['key' => 'is_active', 'label' => 'الحالة'], ['key' => 'buildings_count', 'label' => 'عدد المباني']],
            'rows' => $rows,
        ];
    }

    private function buildings(array $params): array
    {
        $rows = Building::withCount('units')->when(! empty($params['location_id']), fn ($q) => $q->where('location_id', $params['location_id']))
            ->orderBy('id')->get()->values()->map(fn ($b, $i) => [
                $i + 1, $b->name, $b->location?->name ?? '', ltrim($b->floors ?? ''), self::LOCATION_ACTIVE[(bool) $b->is_active] ?? '—', ltrim($b->units_count),
            ]);

        return [
            'title' => 'قائمة المباني',
            'columns' => [['key' => 'id', 'label' => 'الرقم'], ['key' => 'name', 'label' => 'الاسم'], ['key' => 'location', 'label' => 'الموقع'], ['key' => 'floors', 'label' => 'الأدوار'], ['key' => 'is_active', 'label' => 'الحالة'], ['key' => 'units_count', 'label' => 'عدد الوحدات']],
            'rows' => $rows,
        ];
    }

    private function units(array $params, string $currency): array
    {
        $rows = Unit::with(['building.location'])->when(! empty($params['building_id']), fn ($q) => $q->where('building_id', $params['building_id']))
            ->when(! empty($params['status']), fn ($q) => $q->where('status', $params['status']))
            ->orderBy('id')->get()->values()->map(fn ($u, $i) => [
                $i + 1, $u->unit_number, $u->building?->name ?? '', self::UNIT_TYPE[$u->unit_type] ?? $u->unit_type,
                $u->floor ?? '', ltrim($u->area ?? ''), $currency.' '.$this->num($u->rent_amount), self::UNIT_STATUS[$u->status] ?? $u->status,
            ]);

        return [
            'title' => 'قائمة الوحدات',
            'columns' => [['key' => 'id', 'label' => 'الرقم'], ['key' => 'unit_number', 'label' => 'رقم الوحدة'], ['key' => 'building', 'label' => 'المبنى'], ['key' => 'unit_type', 'label' => 'النوع'], ['key' => 'floor', 'label' => 'الطابق'], ['key' => 'area', 'label' => 'المساحة'], ['key' => 'rent_amount', 'label' => 'الإيجار'], ['key' => 'status', 'label' => 'الحالة']],
            'rows' => $rows,
        ];
    }

    private function tenants(): array
    {
        $rows = Tenant::orderBy('id')->get()->values()->map(fn ($t, $i) => [
            $i + 1, ($t->first_name).' '.($t->last_name), $t->phone ?: '', $t->email ?: '', $t->id_number ?: '', self::LOCATION_ACTIVE[(bool) $t->is_active] ?? '—',
        ]);

        return [
            'title' => 'قائمة المستأجرين',
            'columns' => [['key' => 'id', 'label' => 'الرقم'], ['key' => 'name', 'label' => 'الاسم'], ['key' => 'phone', 'label' => 'الهاتف'], ['key' => 'email', 'label' => 'البريد'], ['key' => 'id_number', 'label' => 'الهوية'], ['key' => 'is_active', 'label' => 'الحالة']],
            'rows' => $rows,
        ];
    }

    private function contracts(string $currency): array
    {
        $rows = Contract::with('tenant', 'unit.building')->orderBy('contract_number')->get()->values()->map(fn ($c, $i) => [
            $i + 1, $c->contract_number, ($c->tenant?->first_name ?? '').' '.($c->tenant?->last_name ?? ''),
            $c->unit?->unit_number ?? '', $c->unit?->building?->name ?? '', $c->start_date?->format('Y-m-d') ?? '', $c->end_date?->format('Y-m-d') ?? '',
            $currency.' '.$this->num($c->rent_amount), self::CONTRACT_STATUS[$c->status] ?? $c->status,
        ]);

        return [
            'title' => 'قائمة العقود',
            'columns' => [['key' => 'id', 'label' => 'الرقم'], ['key' => 'contract_number', 'label' => 'رقم العقد'], ['key' => 'tenant', 'label' => 'المستأجر'], ['key' => 'unit', 'label' => 'الوحدة'], ['key' => 'building', 'label' => 'المبنى'], ['key' => 'start_date', 'label' => 'البداية'], ['key' => 'end_date', 'label' => 'النهاية'], ['key' => 'rent_amount', 'label' => 'الإيجار'], ['key' => 'status', 'label' => 'الحالة']],
            'rows' => $rows,
        ];
    }

    private function invoices(array $params, string $currency): array
    {
        $rows = Invoice::with('contract.tenant', 'contract.unit.building')->when(! empty($params['status']), fn ($q) => $q->where('status', $params['status']))
            ->when(! empty($params['building_id']), fn ($q) => $q->whereHas('contract.unit.building', fn ($b) => $b->where('id', $params['building_id'])))
            ->orderBy('invoice_number')->get()->values()->map(fn ($i, $idx) => [
                $idx + 1, $i->invoice_number, ($i->contract?->tenant?->first_name ?? '').' '.($i->contract?->tenant?->last_name ?? ''),
                $i->contract?->unit?->unit_number ?? '', $i->issue_date?->format('Y-m-d') ?? '', $i->due_date?->format('Y-m-d') ?? '',
                $currency.' '.$this->num($i->total_amount), $currency.' '.$this->num($i->paid_amount), self::INVOICE_STATUS[$i->status] ?? $i->status,
            ]);

        return [
            'title' => 'قائمة الفواتير',
            'columns' => [['key' => 'id', 'label' => 'الرقم'], ['key' => 'invoice_number', 'label' => 'رقم الفاتورة'], ['key' => 'tenant', 'label' => 'المستأجر'], ['key' => 'unit', 'label' => 'الوحدة'], ['key' => 'issue_date', 'label' => 'الإصدار'], ['key' => 'due_date', 'label' => 'الاستحقاق'], ['key' => 'total_amount', 'label' => 'الإجمالي'], ['key' => 'paid_amount', 'label' => 'المدفوع'], ['key' => 'status', 'label' => 'الحالة']],
            'rows' => $rows,
        ];
    }

    private function payments(string $currency): array
    {
        $rows = Payment::with('invoice.contract.tenant')->orderBy('id')->get()->values()->map(fn ($p, $i) => [
            $i + 1, $p->receipt_number, ($p->invoice?->contract?->tenant?->first_name ?? '').' '.($p->invoice?->contract?->tenant?->last_name ?? ''),
            $currency.' '.$this->num($p->amount), $p->payment_date?->format('Y-m-d') ?? '', self::PAYMENT_METHOD[$p->payment_method] ?? $p->payment_method,
        ]);

        return [
            'title' => 'قائمة الدفعات',
            'columns' => [['key' => 'id', 'label' => 'الرقم'], ['key' => 'receipt_number', 'label' => 'رقم الإيصال'], ['key' => 'tenant', 'label' => 'المستأجر'], ['key' => 'amount', 'label' => 'المبلغ'], ['key' => 'payment_date', 'label' => 'التاريخ'], ['key' => 'method', 'label' => 'طريقة الدفع']],
            'rows' => $rows,
        ];
    }

    private function utilities(array $params, string $currency): array
    {
        $rows = UtilityReading::with('unit.building')->when(! empty($params['utility_type']), fn ($q) => $q->where('utility_type', $params['utility_type']))
            ->orderBy('id')->get()->values()->map(fn ($u, $i) => [
                $i + 1, $u->unit?->unit_number ?? '', $u->unit?->building?->name ?? '', self::UTILITY_TYPE[$u->utility_type] ?? $u->utility_type,
                $u->reading_date?->format('Y-m-d') ?? '', $this->num($u->consumption), $currency.' '.$this->num($u->total),
            ]);

        return [
            'title' => 'قائمة قراءات المرافق',
            'columns' => [['key' => 'id', 'label' => 'الرقم'], ['key' => 'unit', 'label' => 'الوحدة'], ['key' => 'building', 'label' => 'المبنى'], ['key' => 'type', 'label' => 'النوع'], ['key' => 'date', 'label' => 'التاريخ'], ['key' => 'consumption', 'label' => 'الاستهلاك'], ['key' => 'total', 'label' => 'المبلغ']],
            'rows' => $rows,
        ];
    }

    private function expenses(array $params, string $currency): array
    {
        $rows = Expense::with('building')->when(! empty($params['building_id']), fn ($q) => $q->where('building_id', $params['building_id']))
            ->when(! empty($params['category']), fn ($q) => $q->where('category', $params['category']))
            ->orderBy('id')->get()->values()->map(fn ($e, $i) => [
                $i + 1, $e->building?->name ?? '', self::EXPENSE_CATEGORY[$e->category] ?? $e->category,
                $currency.' '.$this->num($e->amount), $e->expense_date?->format('Y-m-d') ?? '', $e->description ?: '',
            ]);

        return [
            'title' => 'قائمة المصروفات',
            'columns' => [['key' => 'id', 'label' => 'الرقم'], ['key' => 'building', 'label' => 'المبنى'], ['key' => 'category', 'label' => 'الفئة'], ['key' => 'amount', 'label' => 'المبلغ'], ['key' => 'date', 'label' => 'التاريخ'], ['key' => 'description', 'label' => 'البيان']],
            'rows' => $rows,
        ];
    }

    private function maintenance(array $params = []): array
    {
        $rows = MaintenanceRequest::with('unit.building', 'assignedTo')
            ->when(! empty($params['status']), fn ($q) => $q->where('status', $params['status']))
            ->when(! empty($params['priority']), fn ($q) => $q->where('priority', $params['priority']))
            ->orderBy('id')->get()->values()->map(fn ($m, $i) => [
                $i + 1, $m->description, $m->unit?->unit_number ?? '', $m->unit?->building?->name ?? '',
                self::MAINTENANCE_PRIORITY[$m->priority] ?? $m->priority, self::MAINTENANCE_STATUS[$m->status] ?? $m->status, $m->assignedTo?->name ?? '—',
            ]);

        return [
            'title' => 'قائمة طلبات الصيانة',
            'columns' => [['key' => 'id', 'label' => 'الرقم'], ['key' => 'description', 'label' => 'الوصف'], ['key' => 'unit', 'label' => 'الوحدة'], ['key' => 'building', 'label' => 'المبنى'], ['key' => 'priority', 'label' => 'الأولوية'], ['key' => 'status', 'label' => 'الحالة'], ['key' => 'assigned', 'label' => 'المسؤول']],
            'rows' => $rows,
        ];
    }

    private function users(array $params): array
    {
        $rows = User::when(! empty($params['role']), fn ($q) => $q->where('role', $params['role']))
            ->orderBy('id')->get()->values()->map(fn ($u, $i) => [
                $i + 1, $u->name, $u->email ?: '', $u->phone ?: '', self::ROLE[$u->role] ?? $u->role, self::LOCATION_ACTIVE[(bool) $u->is_active] ?? '—',
            ]);

        return [
            'title' => 'قائمة المستخدمين',
            'columns' => [['key' => 'id', 'label' => 'الرقم'], ['key' => 'name', 'label' => 'الاسم'], ['key' => 'email', 'label' => 'البريد'], ['key' => 'phone', 'label' => 'الهاتف'], ['key' => 'role', 'label' => 'الدور'], ['key' => 'is_active', 'label' => 'الحالة']],
            'rows' => $rows,
        ];
    }

    private function num(mixed $value): string
    {
        $n = (float) $value;

        return number_format($n, 2, '.', '');
    }
}
