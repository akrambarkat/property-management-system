<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationSetting;
use App\Models\User;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\MaintenanceRequest;
use App\Models\SmsLog;
use App\Models\Building;
use App\Models\Unit;
use Carbon\Carbon;

class NotificationService
{
    public function create(array $data): Notification
    {
        $settingType = $this->mapTypeToSetting($data['type'], $data['category'] ?? null);

        $setting = NotificationSetting::where('user_id', $data['user_id'])
            ->where('type', $settingType)
            ->first();

        if ($setting && !$setting->is_enabled) {
            return null;
        }

        if ($setting && !$setting->in_app_enabled && ($data['delivery_channel'] ?? 'in_app') === 'in_app') {
            return null;
        }

        return Notification::create($data);
    }

    public function createForAllAdmins(array $data): void
    {
        $admins = User::whereIn('role', ['super_admin', 'admin'])->where('is_active', true)->get();
        foreach ($admins as $admin) {
            $this->create(array_merge($data, ['user_id' => $admin->id]));
        }
    }

    public function getUnreadCount(int $userId): int
    {
        return Notification::forUser($userId)->unread()->notArchived()->count();
    }

    public function getLatest(int $userId, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return Notification::forUser($userId)
            ->notArchived()
            ->with('creator:id,name')
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function markAsRead(int $id, int $userId): bool
    {
        $notification = Notification::where('id', $id)->where('user_id', $userId)->first();
        if ($notification) {
            $notification->markAsRead();
            return true;
        }
        return false;
    }

    public function markAllAsRead(int $userId): int
    {
        return Notification::forUser($userId)
            ->unread()
            ->update(['is_read' => true, 'read_at' => now()]);
    }

    public function archive(int $id, int $userId): bool
    {
        $notification = Notification::where('id', $id)->where('user_id', $userId)->first();
        if ($notification) {
            $notification->archive();
            return true;
        }
        return false;
    }

    public function delete(int $id, int $userId): bool
    {
        $notification = Notification::where('id', $id)->where('user_id', $userId)->first();
        if ($notification) {
            $notification->delete();
            return true;
        }
        return false;
    }

    public function bulkMarkAsRead(array $ids, int $userId): int
    {
        return Notification::whereIn('id', $ids)
            ->where('user_id', $userId)
            ->unread()
            ->update(['is_read' => true, 'read_at' => now()]);
    }

    public function bulkArchive(array $ids, int $userId): int
    {
        return Notification::whereIn('id', $ids)
            ->where('user_id', $userId)
            ->update(['is_archived' => true, 'archived_at' => now()]);
    }

    public function bulkDelete(array $ids, int $userId): int
    {
        return Notification::whereIn('id', $ids)
            ->where('user_id', $userId)
            ->delete();
    }

    public function getUserSettings(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        $settings = NotificationSetting::where('user_id', $userId)->get()->keyBy('type');
        $defaults = NotificationSetting::defaultTypes();
        $result = [];

        foreach ($defaults as $type => $config) {
            $existing = $settings->get($type);
            $result[$type] = [
                'type' => $type,
                'title' => $config['title'],
                'description' => $config['description'],
                'is_enabled' => $existing?->is_enabled ?? true,
                'in_app_enabled' => $existing?->in_app_enabled ?? true,
                'sms_enabled' => $existing?->sms_enabled ?? false,
                'email_enabled' => $existing?->email_enabled ?? false,
            ];
        }

        return collect($result);
    }

    public function updateUserSetting(int $userId, string $type, array $data): NotificationSetting
    {
        return NotificationSetting::updateOrCreate(
            ['user_id' => $userId, 'type' => $type],
            $data
        );
    }

    // ==========================================
    // AUTO-GENERATION METHODS
    // ==========================================

    public function checkExpiringContracts(int $days = 30): void
    {
        $contracts = Contract::with(['unit.building.location', 'tenant'])
            ->where('status', 'active')
            ->where('end_date', '<=', Carbon::now()->addDays($days))
            ->where('end_date', '>=', Carbon::now())
            ->get();

        foreach ($contracts as $contract) {
            $daysLeft = Carbon::now()->diffInDays(Carbon::parse($contract->end_date));
            $unitNumber = $contract->unit->unit_number ?? '—';
            $tenantName = $contract->tenant ? "{$contract->tenant->first_name} {$contract->tenant->last_name}" : '—';

            $priority = match (true) {
                $daysLeft <= 1 => Notification::PRIORITY_CRITICAL,
                $daysLeft <= 7 => Notification::PRIORITY_HIGH,
                $daysLeft <= 15 => Notification::PRIORITY_MEDIUM,
                default => Notification::PRIORITY_LOW,
            };

            $exists = Notification::where('user_id', $this->getAdminUserIds()->first())
                ->where('related_model', Contract::class)
                ->where('related_id', $contract->id)
                ->where('category', Notification::CATEGORY_EXPIRATION)
                ->whereDate('created_at', Carbon::today())
                ->exists();

            if (!$exists) {
                $this->createForAllAdmins([
                    'title' => "عقد ينتهي خلال {$daysLeft} يوم",
                    'message' => "عقد الوحدة #{$unitNumber} للمستأجر {$tenantName} ينتهي بعد {$daysLeft} يوم",
                    'type' => Notification::TYPE_CONTRACT,
                    'priority' => $priority,
                    'category' => Notification::CATEGORY_EXPIRATION,
                    'related_model' => Contract::class,
                    'related_id' => $contract->id,
                    'action_url' => "/contracts/{$contract->id}",
                ]);
            }
        }
    }

    public function checkExpiredContracts(): void
    {
        $contracts = Contract::with(['unit', 'tenant'])
            ->where('status', 'active')
            ->where('end_date', '<', Carbon::now())
            ->get();

        foreach ($contracts as $contract) {
            $unitNumber = $contract->unit->unit_number ?? '—';
            $tenantName = $contract->tenant ? "{$contract->tenant->first_name} {$contract->tenant->last_name}" : '—';

            $exists = Notification::where('related_model', Contract::class)
                ->where('related_id', $contract->id)
                ->where('category', Notification::CATEGORY_EXPIRATION)
                ->where('message', 'like', '%انتهى%')
                ->whereDate('created_at', Carbon::today())
                ->exists();

            if (!$exists) {
                $this->createForAllAdmins([
                    'title' => 'عقد منتهي الصلاحية',
                    'message' => "عقد الوحدة #{$unitNumber} للمستأجر {$tenantName} انتهى صلاحيته",
                    'type' => Notification::TYPE_CONTRACT,
                    'priority' => Notification::PRIORITY_CRITICAL,
                    'category' => Notification::CATEGORY_EXPIRATION,
                    'related_model' => Contract::class,
                    'related_id' => $contract->id,
                    'action_url' => "/contracts/{$contract->id}",
                ]);
            }
        }
    }

    public function checkOverdueInvoices(): void
    {
        $invoices = Invoice::with(['contract.unit', 'contract.tenant'])
            ->where('status', 'unpaid')
            ->where('due_date', '<', Carbon::now())
            ->get();

        foreach ($invoices as $invoice) {
            $daysOverdue = Carbon::parse($invoice->due_date)->diffInDays(now());
            $unitNumber = $invoice->contract?->unit->unit_number ?? '—';
            $tenantName = $invoice->contract?->tenant
                ? "{$invoice->contract->tenant->first_name} {$invoice->contract->tenant->last_name}"
                : '—';

            $priority = match (true) {
                $daysOverdue >= 7 => Notification::PRIORITY_CRITICAL,
                $daysOverdue >= 3 => Notification::PRIORITY_HIGH,
                default => Notification::PRIORITY_MEDIUM,
            };

            $exists = Notification::where('related_model', Invoice::class)
                ->where('related_id', $invoice->id)
                ->where('category', Notification::CATEGORY_OVERDUE)
                ->whereDate('created_at', Carbon::today())
                ->exists();

            if (!$exists) {
                $this->createForAllAdmins([
                    'title' => "فاتورة متأخرة {$daysOverdue} يوم",
                    'message' => "فاتورة #{$invoice->invoice_number} للوحدة #{$unitNumber} ({$tenantName}) متأخرة السداد",
                    'type' => Notification::TYPE_INVOICE,
                    'priority' => $priority,
                    'category' => Notification::CATEGORY_OVERDUE,
                    'related_model' => Invoice::class,
                    'related_id' => $invoice->id,
                    'action_url' => "/invoices",
                ]);
            }
        }
    }

    public function notifyInvoiceCreated(Invoice $invoice): void
    {
        $unitNumber = $invoice->contract?->unit->unit_number ?? '—';
        $tenantName = $invoice->contract?->tenant
            ? "{$invoice->contract->tenant->first_name} {$invoice->contract->tenant->last_name}"
            : '—';

        $this->createForAllAdmins([
            'title' => 'فاتورة جديدة',
            'message' => "تم إنشاء فاتورة #{$invoice->invoice_number} للوحدة #{$unitNumber} ({$tenantName})",
            'type' => Notification::TYPE_INVOICE,
            'priority' => Notification::PRIORITY_LOW,
            'category' => Notification::CATEGORY_CREATED,
            'related_model' => Invoice::class,
            'related_id' => $invoice->id,
            'action_url' => "/invoices",
        ]);
    }

    public function notifyInvoicePaid(Invoice $invoice, float $amount): void
    {
        $unitNumber = $invoice->contract?->unit->unit_number ?? '—';

        $this->createForAllAdmins([
            'title' => 'فاتورة مدفوعة',
            'message' => "تم سداد مبلغ {$amount}₪ من فاتورة #{$invoice->invoice_number} للوحدة #{$unitNumber}",
            'type' => Notification::TYPE_INVOICE,
            'priority' => Notification::PRIORITY_LOW,
            'category' => Notification::CATEGORY_PAYMENT,
            'related_model' => Invoice::class,
            'related_id' => $invoice->id,
            'action_url' => "/invoices",
        ]);
    }

    public function notifyTenantCreated(Tenant $tenant): void
    {
        $this->createForAllAdmins([
            'title' => 'مستأجر جديد',
            'message' => "تم إضافة المستأجر {$tenant->first_name} {$tenant->last_name} بنجاح",
            'type' => Notification::TYPE_TENANT,
            'priority' => Notification::PRIORITY_LOW,
            'category' => Notification::CATEGORY_CREATED,
            'related_model' => Tenant::class,
            'related_id' => $tenant->id,
            'action_url' => "/tenants/{$tenant->id}",
        ]);
    }

    public function notifyMaintenanceCreated(MaintenanceRequest $request): void
    {
        $unitNumber = $request->unit->unit_number ?? '—';

        $priority = match ($request->priority) {
            'urgent' => Notification::PRIORITY_CRITICAL,
            'high' => Notification::PRIORITY_HIGH,
            'medium' => Notification::PRIORITY_MEDIUM,
            default => Notification::PRIORITY_LOW,
        };

        $this->createForAllAdmins([
            'title' => 'طلب صيانة جديد',
            'message' => "طلب صيانة للوحدة #{$unitNumber}: " . mb_substr($request->description, 0, 60),
            'type' => Notification::TYPE_MAINTENANCE,
            'priority' => $priority,
            'category' => Notification::CATEGORY_CREATED,
            'related_model' => MaintenanceRequest::class,
            'related_id' => $request->id,
            'action_url' => "/maintenance",
        ]);
    }

    public function notifyMaintenanceCompleted(MaintenanceRequest $request): void
    {
        $unitNumber = $request->unit->unit_number ?? '—';

        $this->createForAllAdmins([
            'title' => 'تم إتمام الصيانة',
            'message' => "تم إتمام طلب الصيانة للوحدة #{$unitNumber} بنجاح",
            'type' => Notification::TYPE_MAINTENANCE,
            'priority' => Notification::PRIORITY_LOW,
            'category' => Notification::CATEGORY_COMPLETED,
            'related_model' => MaintenanceRequest::class,
            'related_id' => $request->id,
            'action_url' => "/maintenance",
        ]);
    }

    public function notifySmsFailed(SmsLog $smsLog, string $reason): void
    {
        $this->createForAllAdmins([
            'title' => 'فشل إرسال رسالة SMS',
            'message' => "فشل إرسال الرسالة إلى {$smsLog->recipient}: {$reason}",
            'type' => Notification::TYPE_SMS,
            'priority' => Notification::PRIORITY_HIGH,
            'category' => Notification::CATEGORY_FAILED,
            'related_model' => SmsLog::class,
            'related_id' => $smsLog->id,
            'action_url' => "/settings/sms",
        ]);
    }

    public function notifyBuildingCreated(Building $building): void
    {
        $this->createForAllAdmins([
            'title' => 'مبنى جديد',
            'message' => "تم إضافة المبنى {$building->name} بنجاح",
            'type' => Notification::TYPE_BUILDING,
            'priority' => Notification::PRIORITY_LOW,
            'category' => Notification::CATEGORY_CREATED,
            'related_model' => Building::class,
            'related_id' => $building->id,
            'action_url' => "/buildings",
        ]);
    }

    public function notifyUnitCreated(Unit $unit): void
    {
        $buildingName = $unit->building->name ?? '—';

        $this->createForAllAdmins([
            'title' => 'وحدة جديدة',
            'message' => "تم إضافة الوحدة #{$unit->unit_number} في {$buildingName}",
            'type' => Notification::TYPE_BUILDING,
            'priority' => Notification::PRIORITY_LOW,
            'category' => Notification::CATEGORY_CREATED,
            'related_model' => Unit::class,
            'related_id' => $unit->id,
            'action_url' => "/units/{$unit->id}",
        ]);
    }

    public function notifySystemAlert(string $title, string $message, string $priority = Notification::PRIORITY_MEDIUM): void
    {
        $this->createForAllAdmins([
            'title' => $title,
            'message' => $message,
            'type' => Notification::TYPE_SYSTEM,
            'priority' => $priority,
            'category' => Notification::CATEGORY_ALERT,
            'action_url' => null,
        ]);
    }

    private function mapTypeToSetting(string $type, ?string $category): string
    {
        return match (true) {
            $type === Notification::TYPE_CONTRACT && $category === Notification::CATEGORY_EXPIRATION => 'contract_expiration',
            $type === Notification::TYPE_INVOICE && $category === Notification::CATEGORY_OVERDUE => 'overdue_invoice',
            $type === Notification::TYPE_INVOICE && $category === Notification::CATEGORY_PAYMENT => 'new_payment',
            $type === Notification::TYPE_MAINTENANCE => 'maintenance_request',
            $type === Notification::TYPE_SMS && $category === Notification::CATEGORY_FAILED => 'sms_failure',
            $type === Notification::TYPE_SYSTEM => 'system_alert',
            default => 'system_alert',
        };
    }

    private function getAdminUserIds()
    {
        return User::whereIn('role', ['super_admin', 'admin'])
            ->where('is_active', true)
            ->pluck('id');
    }
}
