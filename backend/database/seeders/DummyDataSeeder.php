<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Building;
use App\Models\Unit;
use App\Models\Tenant;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\MaintenanceRequest;
use App\Models\Expense;
use App\Models\UtilityReading;
use App\Models\SmsLog;
use App\Models\Notification;
use App\Models\ActivityLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DummyDataSeeder extends Seeder
{
    private int $adminId;

    public function run(): void
    {
        $this->adminId = User::whereIn('role', ['super_admin', 'admin'])->first()?->id ?? 1;

        $this->command->info('Truncating existing dummy data...');
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('activity_logs')->truncate();
        DB::table('notification_settings')->truncate();
        DB::table('notifications')->truncate();
        DB::table('sms_logs')->truncate();
        DB::table('utility_readings')->truncate();
        DB::table('expenses')->truncate();
        DB::table('maintenance_requests')->truncate();
        DB::table('payments')->truncate();
        DB::table('invoices')->truncate();
        DB::table('contracts')->truncate();
        DB::table('tenants')->truncate();
        DB::table('units')->truncate();
        DB::table('buildings')->truncate();
        DB::table('locations')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $locations = $this->createLocations();
        $buildings = $this->createBuildings($locations);
        $units = $this->createUnits($buildings);
        $tenants = $this->createTenants();
        $contracts = $this->createContracts($units, $tenants);
        $this->createInvoicesAndPayments($contracts);
        $this->createMaintenanceRequests($units);
        $this->createExpenses($buildings, $units);
        $this->createUtilityReadings($units);
        $this->createSmsLogs($tenants);
        $this->createNotifications();
        $this->createActivityLogs();

        $this->command->info('All dummy data seeded successfully!');
    }

    private function createLocations(): array
    {
        $data = [
            ['name' => 'رام الله - البريد', 'address' => 'شارع البريد، رام الله'],
            ['name' => 'البيرة - وسط البلد', 'address' => 'شارع الشهداء، البيرة'],
            ['name' => 'نابلس - رافات', 'address' => 'شارع رافات، نابلس'],
            ['name' => 'بيت لحم - الميدان', 'address' => 'شارع الميدان، بيت لحم'],
            ['name' => 'جنين - الحمراء', 'address' => 'شارع الحمراء، جنين'],
        ];
        return array_map(fn($d) => Location::create($d), $data);
    }

    private function createBuildings(array $locations): array
    {
        $names = ['برج الأمل', 'مبنى السلام', 'برج النور', 'مبنى الياسمين', 'برج الزهراء', 'مبنى الفردوس', 'برج الريادة', 'مبنى النهضة', 'برج الكرمل', 'مبنى الورود'];
        $buildings = [];
        $idx = 0;
        foreach ($locations as $loc) {
            $count = $idx < 3 ? 3 : 2;
            for ($i = 0; $i < $count; $i++) {
                $buildings[] = Building::create([
                    'location_id' => $loc->id,
                    'name' => $names[$idx % count($names)],
                    'address' => $loc->address . ' - مبنى ' . ($i + 1),
                    'floors' => rand(4, 10),
                ]);
                $idx++;
            }
        }
        return $buildings;
    }

    private function createUnits(array $buildings): array
    {
        $units = [];
        $types = ['apartment', 'apartment', 'apartment', 'shop', 'warehouse'];
        foreach ($buildings as $b) {
            $floors = $b->floors;
            $unitNum = 100;
            for ($f = 1; $f <= min($floors, 5); $f++) {
                $perFloor = $f == 1 ? 2 : 3;
                for ($u = 0; $u < $perFloor; $u++) {
                    $type = $f == 1 && $u == 0 ? 'shop' : ($f == 1 && $u == 1 ? 'warehouse' : 'apartment');
                    $units[] = Unit::create([
                        'building_id' => $b->id,
                        'unit_number' => (string)$unitNum,
                        'unit_type' => $type,
                        'floor' => $f,
                        'area' => $type === 'apartment' ? rand(80, 200) : rand(40, 120),
                        'rent_amount' => $type === 'apartment' ? rand(200, 600) : rand(300, 800),
                        'electricity_amount' => rand(50, 150),
                        'water_amount' => rand(20, 60),
                        'internet_amount' => 30,
                        'services_amount' => 40,
                        'status' => 'available',
                    ]);
                    $unitNum++;
                }
            }
        }
        return $units;
    }

    private function createTenants(): array
    {
        $tenants = [
            ['first_name' => 'خالد', 'last_name' => 'العلي', 'phone' => '0599123456', 'email' => 'khalid@email.com'],
            ['first_name' => 'أحمد', 'last_name' => 'محمد', 'phone' => '0599234567', 'email' => 'ahmed@email.com'],
            ['first_name' => 'محمد', 'last_name' => 'سعيد', 'phone' => '0599345678', 'email' => 'mohammad@email.com'],
            ['first_name' => 'علي', 'last_name' => 'حسين', 'phone' => '0599456789', 'email' => 'ali@email.com'],
            ['first_name' => 'عمر', 'last_name' => 'عبدالرحمن', 'phone' => '0599567890', 'email' => 'omar@email.com'],
            ['first_name' => 'ياسر', 'last_name' => 'أحمد', 'phone' => '0599678901', 'email' => 'yasser@email.com'],
            ['first_name' => 'جمال', 'last_name' => 'الدويك', 'phone' => '0599789012', 'email' => 'jamal@email.com'],
            ['first_name' => 'تامر', 'last_name' => 'برغوث', 'phone' => '0599890123', 'email' => 'tamer@email.com'],
            ['first_name' => 'سامي', 'last_name' => 'الحليمي', 'phone' => '0599901234', 'email' => 'sami@email.com'],
            ['first_name' => 'نبيل', 'last_name' => 'شتيوي', 'phone' => '0599012345', 'email' => 'nabil@email.com'],
            ['first_name' => 'حمزة', 'last_name' => 'الخالدي', 'phone' => '0599111222', 'email' => 'hamza@email.com'],
            ['first_name' => 'باسم', 'last_name' => 'قمر', 'phone' => '0599222333', 'email' => 'basem@email.com'],
            ['first_name' => 'وسيم', 'last_name' => 'حجازي', 'phone' => '0599333444', 'email' => 'waseem@email.com'],
            ['first_name' => 'ماهر', 'last_name' => 'الشيخ', 'phone' => '0599444555', 'email' => 'mahir@email.com'],
            ['first_name' => 'فراس', 'last_name' => 'كمال', 'phone' => '0599555666', 'email' => 'firas@email.com'],
        ];

        $result = [];
        foreach ($tenants as $i => $t) {
            $result[] = Tenant::create([
                'first_name' => $t['first_name'],
                'last_name' => $t['last_name'],
                'id_number' => str_pad((string)(100000000 + $i), 9, '0'),
                'phone' => $t['phone'],
                'email' => $t['email'],
                'address' => 'فلسطين',
            ]);
        }
        return $result;
    }

    private function createContracts(array $units, array $tenants): array
    {
        $contracts = [];
        $available = array_slice($units, 0, 15);
        foreach ($available as $i => $unit) {
            $tenant = $tenants[$i % count($tenants)];
            $start = Carbon::now()->subMonths(rand(1, 10));
            $end = (clone $start)->addMonths(rand(6, 12));
            $status = $end->isPast() ? 'expired' : 'active';

            $contracts[] = Contract::create([
                'contract_number' => 'CNT-' . str_pad((string)($i + 1), 4, '0', STR_PAD_LEFT),
                'unit_id' => $unit->id,
                'tenant_id' => $tenant->id,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'rent_amount' => $unit->rent_amount,
                'contract_type' => 'monthly',
                'status' => $status,
            ]);

            if ($status === 'active') {
                $unit->update(['status' => 'occupied']);
            }
        }
        return $contracts;
    }

    private function createInvoicesAndPayments(array $contracts): void
    {
        $invNum = 1;
        foreach ($contracts as $contract) {
            $months = $contract->status === 'expired' ? 6 : rand(1, 3);
            for ($m = 0; $m < $months; $m++) {
                $issue = Carbon::parse($contract->start_date)->addMonths($m);
                $due = (clone $issue)->addDays(15);
                $paid = $m < $months - 1 || $contract->status === 'expired';
                $paidAmount = $paid ? $contract->rent_amount : 0;

                $inv = Invoice::create([
                    'contract_id' => $contract->id,
                    'invoice_number' => 'INV-' . str_pad((string)$invNum, 4, '0', STR_PAD_LEFT),
                    'issue_date' => $issue->toDateString(),
                    'due_date' => $due->toDateString(),
                    'rent_amount' => $contract->rent_amount,
                    'electricity_amount' => rand(50, 150),
                    'water_amount' => rand(20, 60),
                    'internet_amount' => 30,
                    'services_amount' => 40,
                    'total_amount' => $contract->rent_amount + rand(100, 280),
                    'status' => $paid ? 'paid' : ($due->isPast() ? 'overdue' : 'unpaid'),
                    'paid_amount' => $paidAmount,
                ]);

                if ($paid) {
                    Payment::create([
                        'invoice_id' => $inv->id,
                        'amount' => $paidAmount,
                        'payment_date' => $due->subDays(rand(0, 5))->toDateString(),
                        'payment_method' => ['cash', 'bank_transfer', 'check'][rand(0, 2)],
                        'receipt_number' => 'RCP-' . str_pad((string)$invNum, 4, '0', STR_PAD_LEFT),
                        'created_by' => $this->adminId,
                    ]);
                }
                $invNum++;
            }
        }
    }

    private function createMaintenanceRequests(array $units): void
    {
        $descriptions = [
            'تسريب مياه في الحمام', 'عطل في المكيف المركزي', 'عطل في لوحة الكهرباء',
            'كسر في الزجاج', 'عطل في القفل', 'تسريب غاز', 'صيانة الدراج الكهربائي',
            'عطل في سخان المياه', 'طلاء الجدران', 'إصلاح السقف',
        ];
        $priorities = ['low', 'medium', 'high', 'urgent'];
        $statuses = ['pending', 'in_progress', 'completed', 'cancelled'];
        $sample = array_slice($units, 0, 10);

        foreach ($sample as $i => $unit) {
            $status = $statuses[$i % count($statuses)];
            MaintenanceRequest::create([
                'unit_id' => $unit->id,
                'requested_by' => $this->adminId,
                'description' => $descriptions[$i % count($descriptions)],
                'priority' => $priorities[$i % count($priorities)],
                'status' => $status,
                'assigned_to' => $status !== 'pending' ? $this->adminId : null,
                'cost' => in_array($status, ['completed']) ? rand(50, 500) : null,
                'completed_at' => $status === 'completed' ? Carbon::now()->subDays(rand(1, 10)) : null,
            ]);
        }
    }

    private function createExpenses(array $buildings, array $units): void
    {
        $categories = ['maintenance', 'plumbing', 'electrical', 'cleaning', 'security', 'general'];
        $descriptions = ['صيانة عامة', 'تنظيف المبنى', 'فتح مجاري', 'إصلاح كهرباء', 'حراسة شهرية', 'دهان وصيانة', 'صرف مياه'];

        foreach (array_slice($buildings, 0, 6) as $i => $building) {
            Expense::create([
                'building_id' => $building->id,
                'unit_id' => null,
                'category' => $categories[$i % count($categories)],
                'amount' => rand(100, 2000),
                'expense_date' => Carbon::now()->subDays(rand(1, 60))->toDateString(),
                'description' => $descriptions[$i % count($descriptions)],
                'created_by' => $this->adminId,
            ]);
        }

        foreach (array_slice($units, 0, 4) as $i => $unit) {
            Expense::create([
                'building_id' => $unit->building_id,
                'unit_id' => $unit->id,
                'category' => $categories[$i % count($categories)],
                'amount' => rand(50, 500),
                'expense_date' => Carbon::now()->subDays(rand(1, 30))->toDateString(),
                'description' => 'صيانة وحدة رقم ' . $unit->unit_number,
                'created_by' => $this->adminId,
            ]);
        }
    }

    private function createUtilityReadings(array $units): void
    {
        $sample = array_slice($units, 0, 8);
        foreach ($sample as $unit) {
            $prev = rand(100, 500);
            $curr = $prev + rand(20, 150);
            UtilityReading::create([
                'unit_id' => $unit->id,
                'reading_date' => Carbon::now()->subDays(rand(1, 30))->toDateString(),
                'utility_type' => 'electricity',
                'previous_reading' => $prev,
                'current_reading' => $curr,
                'consumption' => $curr - $prev,
                'unit_price' => 0.5,
                'total' => ($curr - $prev) * 0.5,
                'recorded_by' => $this->adminId,
            ]);

            $prev2 = rand(50, 200);
            $curr2 = $prev2 + rand(10, 80);
            UtilityReading::create([
                'unit_id' => $unit->id,
                'reading_date' => Carbon::now()->subDays(rand(1, 30))->toDateString(),
                'utility_type' => 'water',
                'previous_reading' => $prev2,
                'current_reading' => $curr2,
                'consumption' => $curr2 - $prev2,
                'unit_price' => 2.0,
                'total' => ($curr2 - $prev2) * 2.0,
                'recorded_by' => $this->adminId,
            ]);
        }
    }

    private function createSmsLogs(array $tenants): void
    {
        $statuses = ['sent', 'sent', 'sent', 'failed'];
        $messages = [
            'تذكير بدفع الإيجار الشهر', 'فاتورة جديدة صادرة', 'تم استلام الدفعة بنجاح',
            'تنبيه بانتهاء العقد', 'تحديث حالة الصيانة', 'رسالة ترحيب',
        ];

        foreach (array_slice($tenants, 0, 8) as $i => $tenant) {
            SmsLog::create([
                'uuid' => Str::uuid(),
                'recipient' => $tenant->phone,
                'message' => $messages[$i % count($messages)],
                'status' => $statuses[$i % count($statuses)],
                'attempts' => 1,
                'cost' => 0.05,
                'duration_ms' => rand(200, 2000),
                'created_by' => $this->adminId,
                'sent_at' => Carbon::now()->subDays(rand(0, 30)),
            ]);
        }
    }

    private function createNotifications(): void
    {
        $notifications = [
            ['title' => 'عقد ينتهي خلال 7 أيام', 'message' => 'عقد الوحدة #101 ينتهي بعد 7 أيام', 'type' => 'contract', 'priority' => 'high', 'category' => 'expiration', 'action_url' => '/contracts'],
            ['title' => 'فاتورة متأخرة 5 أيام', 'message' => 'فاتورة INV-0003 متأخرة السداد', 'type' => 'invoice', 'priority' => 'critical', 'category' => 'overdue', 'action_url' => '/invoices'],
            ['title' => 'طلب صيانة جديد', 'message' => 'طلب صيانة للوحدة #102: تسريب مياه', 'type' => 'maintenance', 'priority' => 'medium', 'category' => 'created', 'action_url' => '/maintenance'],
            ['title' => 'مستأجر جديد', 'message' => 'تم إضافة المستأجر خالد العلي', 'type' => 'tenant', 'priority' => 'low', 'category' => 'created', 'action_url' => '/tenants'],
            ['title' => 'فاتورة مدفوعة', 'message' => 'تم سداد مبلغ 350₪ من فاتورة INV-0001', 'type' => 'invoice', 'priority' => 'low', 'category' => 'payment', 'action_url' => '/invoices'],
            ['title' => 'مبنى جديد', 'message' => 'تم إضافة المبنى برج الأمل', 'type' => 'building', 'priority' => 'low', 'category' => 'created', 'action_url' => '/buildings'],
            ['title' => 'فشل إرسال رسالة', 'message' => 'فشل إرسال SMS إلى 0599123456', 'type' => 'sms', 'priority' => 'high', 'category' => 'failed', 'action_url' => '/settings/sms'],
            ['title' => 'عقد منتهي الصلاحية', 'message' => 'عقد الوحدة #103 انتهى صلاحيته', 'type' => 'contract', 'priority' => 'critical', 'category' => 'expiration', 'action_url' => '/contracts'],
            ['title' => 'تنبيه النظام', 'message' => 'تم إنشاء نسخة احتياطية بنجاح', 'type' => 'system', 'priority' => 'low', 'category' => 'alert', 'action_url' => null],
            ['title' => 'تم إتمام الصيانة', 'message' => 'تم إصلاح التسريب في الوحدة #101', 'type' => 'maintenance', 'priority' => 'low', 'category' => 'completed', 'action_url' => '/maintenance'],
        ];

        foreach ($notifications as $i => $n) {
            Notification::create(array_merge($n, [
                'user_id' => $this->adminId,
                'is_read' => $i > 4,
                'read_at' => $i > 4 ? Carbon::now()->subHours(rand(1, 24)) : null,
                'created_by' => $this->adminId,
            ]));
        }
    }

    private function createActivityLogs(): void
    {
        $actions = [
            ['action' => 'created', 'model_type' => 'Location', 'model_id' => 1, 'description' => 'تم إضافة موقع جديد'],
            ['action' => 'created', 'model_type' => 'Building', 'model_id' => 1, 'description' => 'تم إضافة مبنى جديد'],
            ['action' => 'created', 'model_type' => 'Unit', 'model_id' => 1, 'description' => 'تم إضافة وحدة جديدة'],
            ['action' => 'updated', 'model_type' => 'Tenant', 'model_id' => 1, 'description' => 'تم تعديل بيانات المستأجر'],
            ['action' => 'created', 'model_type' => 'Contract', 'model_id' => 1, 'description' => 'تم إنشاء عقد جديد'],
            ['action' => 'created', 'model_type' => 'Invoice', 'model_id' => 1, 'description' => 'تم إنشاء فاتورة جديدة'],
            ['action' => 'created', 'model_type' => 'Payment', 'model_id' => 1, 'description' => 'تم تسجيل دفعة جديدة'],
        ];

        foreach ($actions as $i => $a) {
            ActivityLog::create([
                'user_id' => $this->adminId,
                'action' => $a['action'],
                'model_type' => $a['model_type'],
                'model_id' => $a['model_id'],
                'description' => $a['description'],
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Seeder',
            ]);
        }
    }
}
