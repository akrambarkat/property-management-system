<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\SmsJob;
use App\Models\SmsTemplate;
use App\Services\SmsService;
use App\Services\SmsTemplateService;
use Illuminate\Console\Command;

class RunSmsScheduler extends Command
{
    protected $signature = 'sms:schedule';

    protected $description = 'Run automatic SMS rules (due reminders, contract expiry, ...)';

    public function handle(SmsService $sms, SmsTemplateService $templateService): int
    {
        if (!settings('sms_enabled', true, 'sms')) {
            $this->warn('SMS معطّل في الإعدادات.');
            return self::SUCCESS;
        }

        $jobs = SmsJob::where('is_active', true)->get();
        if ($jobs->isEmpty()) {
            $this->info('لا توجد قواعد إرسال مفعّلة.');
            return self::SUCCESS;
        }

        $dispatched = 0;

        foreach ($jobs as $job) {
            $template = $job->template;
            if (!$template || !$template->is_active) {
                continue;
            }

            $targets = match ($job->event_type) {
                'rent_due' => $this->rentDueTargets($job->days_before),
                'contract_expiry' => $this->contractExpiryTargets($job->days_before),
                'payment_confirmation' => $this->recentPayments(),
                'maintenance' => $this->maintenanceUpdates(),
                'payment_failed' => $this->paymentFailed(),
                default => [],
            };

            foreach ($targets as $target) {
                if (empty($target['phone'])) {
                    continue;
                }
                $message = $templateService->render($template->message, $target['data'] ?? []);
                $sms->queue($target['phone'], $message, [
                    'template_id' => $template->id,
                    'dedupe_key' => 'rule:' . $job->id . ':' . $target['phone'] . ':' . md5($message),
                ]);
                $dispatched++;
            }

            $job->update(['last_run_at' => now()]);
        }

        $this->info("تمت جدولة {$dispatched} رسالة.");
        return self::SUCCESS;
    }

    private function rentDueTargets(?int $daysBefore): array
    {
        $daysBefore = $daysBefore ?? 3;
        $dueDate = now()->addDays($daysBefore)->format('Y-m-d');

        return Invoice::where('status', '!=', 'paid')
            ->whereDate('due_date', $dueDate)
            ->with('contract.tenant:id,phone,first_name,last_name', 'contract.unit:id,unit_number', 'contract.unit.building:id,name')
            ->get()
            ->map(function (Invoice $invoice) {
                $c = $invoice->contract;
                return [
                    'phone' => $c?->tenant?->phone,
                    'data' => [
                        'tenant_name' => $c?->tenant ? ($c->tenant->first_name . ' ' . $c->tenant->last_name) : null,
                        'invoice_number' => $invoice->invoice_number,
                        'amount' => $invoice->total_amount,
                        'remaining' => $invoice->paid_amount !== null ? $invoice->total_amount - $invoice->paid_amount : $invoice->total_amount,
                        'due_date' => $invoice->due_date?->format('Y-m-d'),
                        'company' => settings('company_name', '', 'company'),
                    ],
                ];
            })->all();
    }

    private function contractExpiryTargets(?int $daysBefore): array
    {
        $daysBefore = $daysBefore ?? 30;

        return Contract::where('status', 'active')
            ->whereBetween('end_date', [now()->startOfDay(), now()->addDays($daysBefore)->endOfDay()])
            ->with('tenant:id,phone,first_name,last_name', 'unit:id,unit_number', 'unit.building:id,name')
            ->get()
            ->map(function (Contract $contract) {
                return [
                    'phone' => $contract->tenant?->phone,
                    'data' => [
                        'tenant_name' => $contract->tenant ? ($contract->tenant->first_name . ' ' . $contract->tenant->last_name) : null,
                        'contract_end' => $contract->end_date?->format('Y-m-d'),
                        'unit' => $contract->unit?->unit_number,
                        'building' => $contract->unit?->building?->name,
                        'company' => settings('company_name', '', 'company'),
                    ],
                ];
            })->all();
    }

    private function recentPayments(): array
    {
        return Invoice::whereDate('paid_at', today())
            ->with('contract.tenant:id,phone,first_name,last_name')
            ->get()
            ->map(fn (Invoice $invoice) => [
                'phone' => $invoice->contract?->tenant?->phone,
                'data' => [
                    'tenant_name' => $invoice->contract?->tenant ? ($invoice->contract->tenant->first_name . ' ' . $invoice->contract->tenant->last_name) : null,
                    'invoice_number' => $invoice->invoice_number,
                    'amount' => $invoice->total_amount,
                    'payment_date' => $invoice->paid_at?->format('Y-m-d'),
                ],
            ])->all();
    }

    private function maintenanceUpdates(): array
    {
        return \App\Models\MaintenanceRequest::whereDate('updated_at', today())
            ->with('unit:id,unit_number,tenant_id', 'unit.tenant:id,phone,first_name,last_name')
            ->get()
            ->map(fn ($mr) => [
                'phone' => $mr->unit?->tenant?->phone,
                'data' => ['unit' => $mr->unit?->unit_number],
            ])->all();
    }

    private function paymentFailed(): array
    {
        return [];
    }
}
