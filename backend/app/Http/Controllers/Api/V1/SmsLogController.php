<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SmsLogResource;
use App\Models\SmsLog;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SmsLogController extends Controller
{
    public function __construct(private readonly ActivityLogService $audit)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $query = SmsLog::with(['provider:id,name,key', 'template:id,title', 'creator:id,name']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('provider_id')) {
            $query->where('provider_id', $request->input('provider_id'));
        }
        if ($request->filled('recipient')) {
            $query->where('recipient', 'like', '%' . $request->input('recipient') . '%');
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $s = $request->input('search');
                $q->where('recipient', 'like', "%{$s}%")
                    ->orWhere('message_id', 'like', "%{$s}%")
                    ->orWhere('message', 'like', "%{$s}%");
            });
        }

        $logs = $query->orderByDesc('created_at')->paginate((int) $request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => SmsLogResource::collection($logs),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    public function show(SmsLog $log): JsonResponse
    {
        $log->load(['provider', 'template', 'creator', 'failures']);
        return response()->json(['success' => true, 'data' => new SmsLogResource($log)]);
    }

    public function retry(SmsLog $log): JsonResponse
    {
        Gate::authorize('send-sms');

        $log->update([
            'status' => SmsLog::STATUS_QUEUED,
            'failure_reason' => null,
            'attempts' => 0,
        ]);
        $this->audit->log('sms.retry', $log, null, "إعادة محاولة إرسال SMS إلى {$log->recipient}");

        return response()->json(['success' => true, 'message' => 'تمت إعادة وضع الرسالة في قائمة الانتظار']);
    }

    public function export(Request $request): StreamedResponse
    {
        Gate::authorize('export-logs');

        $format = $request->input('format', 'csv');
        $status = $request->input('status');
        $providerId = $request->input('provider_id');

        $query = SmsLog::with('provider');
        if ($status) $query->where('status', $status);
        if ($providerId) $query->where('provider_id', $providerId);

        $logs = $query->orderByDesc('created_at')->get();

        $filename = 'sms_logs_' . now()->format('Ymd_His');

        if ($format === 'excel') {
            $delimiter = ';'; // Excel Arabic support
            $mime = 'text/csv';
            $ext = 'csv';
        } else {
            $delimiter = ',';
            $mime = 'text/csv';
            $ext = 'csv';
        }

        $headers = [
            'Content-Type' => $mime,
            'Content-Disposition' => 'attachment; filename="' . $filename . '.' . $ext . '"',
            'Content-Encoding' => 'UTF-8',
            'Cache-Control' => 'no-store',
        ];

        return response()->stream(function () use ($logs, $delimiter) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                'الحالة', 'المستلم', 'الرسالة', 'المزود', 'التكلفة',
                'المحاولات', 'معرّف الرسالة', 'تاريخ الإرسال', 'المدة (ms)',
            ], $delimiter);

            foreach ($logs as $log) {
                fputcsv($out, [
                    $log->status,
                    $log->recipient,
                    $log->message,
                    $log->provider?->name,
                    $log->cost,
                    $log->attempts,
                    $log->message_id,
                    $log->sent_at?->format('Y-m-d H:i:s'),
                    $log->duration_ms,
                ], $delimiter);
            }
            fclose($out);
        }, 200, $headers);
    }
}
