<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkSmsRequest;
use App\Http\Requests\SendSmsRequest;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\SmsTemplate;
use App\Models\Tenant;
use App\Services\SmsService;
use App\Services\SmsTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SmsController extends Controller
{
    public function __construct(
        private readonly SmsService $sms,
        private readonly SmsTemplateService $templateService,
    ) {
    }

    /**
     * Resolve recipients based on a target scope.
     */
    public function recipients(Request $request): JsonResponse
    {
        $scope = $request->input('scope');
        $phones = [];

        switch ($scope) {
            case 'tenants':
                $phones = Tenant::where('is_active', true)->whereNotNull('phone')->pluck('phone')->all();
                break;
            case 'building':
                $phones = $this->phonesForBuilding((int) $request->input('building_id'));
                break;
            case 'property':
                $phones = $this->phonesForProperty((int) $request->input('location_id'));
                break;
            case 'expiring_contracts':
                $phones = $this->phonesForExpiringContracts((int) ($request->input('days', 30)));
                break;
            case 'overdue_invoices':
                $phones = $this->phonesForOverdueInvoices();
                break;
            case 'custom':
                $raw = $request->input('phones', []);
                $phones = collect(is_string($raw) ? explode(',', $raw) : $raw)
                    ->map(fn ($p) => trim((string) $p))
                    ->filter()
                    ->unique()
                    ->all();
                break;
            default:
                return response()->json(['success' => false, 'message' => 'نطاق مستلمين غير معروف'], 422);
        }

        $count = count($phones);
        return response()->json([
            'success' => true,
            'data' => ['recipients' => array_values($phones), 'count' => $count],
        ]);
    }

    public function send(SendSmsRequest $request): JsonResponse
    {
        $message = $request->input('message');
        $template = SmsTemplate::find($request->input('template_id'));
        if ($template) {
            $message = $this->templateService->render($template->message, $request->input('data', []));
        }

        $log = $this->sms->queue($request->input('recipient'), $message, [
            'template_id' => $template?->id,
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تمت إضافة الرسالة إلى قائمة الانتظار',
            'data' => ['log_id' => $log->id],
        ]);
    }

    public function bulk(BulkSmsRequest $request): JsonResponse
    {
        $template = SmsTemplate::find($request->input('template_id'));
        $baseMessage = $template
            ? $this->templateService->render($template->message, $request->input('data', []))
            : $request->input('message');

        $count = 0;
        foreach ($request->input('recipients') as $recipient) {
            $this->sms->queue($recipient, $baseMessage, [
                'template_id' => $template?->id,
                'created_by' => auth()->id(),
            ]);
            $count++;
        }

        return response()->json([
            'success' => true,
            'message' => "تمت إضافة {$count} رسالة إلى قائمة الانتظار",
            'data' => ['queued' => $count],
        ]);
    }

    private function phonesForBuilding(int $buildingId): array
    {
        return Contract::where('status', 'active')
            ->whereHas('unit', fn ($q) => $q->where('building_id', $buildingId))
            ->with('tenant:id,phone')
            ->get()
            ->pluck('tenant.phone')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function phonesForProperty(int $locationId): array
    {
        return Contract::where('status', 'active')
            ->whereHas('unit.building', fn ($q) => $q->where('location_id', $locationId))
            ->with('tenant:id,phone')
            ->get()
            ->pluck('tenant.phone')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function phonesForExpiringContracts(int $days): array
    {
        return Contract::where('status', 'active')
            ->whereBetween('end_date', [now()->startOfDay(), now()->addDays($days)->endOfDay()])
            ->with('tenant:id,phone')
            ->get()
            ->pluck('tenant.phone')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function phonesForOverdueInvoices(): array
    {
        return Invoice::where('status', 'overdue')
            ->with('contract.tenant:id,phone')
            ->get()
            ->pluck('contract.tenant.phone')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
