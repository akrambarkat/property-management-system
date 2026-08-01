<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SmsLog;
use App\Models\SmsStatistic;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SmsStatisticController extends Controller
{
    public function overview(): JsonResponse
    {
        $today = now()->format('Y-m-d');
        $monthStart = now()->startOfMonth()->format('Y-m-d');

        $todayStats = SmsLog::whereDate('created_at', $today)
            ->selectRaw("COUNT(*) as total,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed")
            ->first();

        $monthStats = SmsLog::whereDate('created_at', '>=', $monthStart)
            ->selectRaw("COUNT(*) as total,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                AVG(duration_ms) as avg_duration")
            ->first();

        $pending = SmsLog::whereIn('status', ['pending', 'queued', 'retrying'])->count();

        $topProvider = SmsLog::whereDate('created_at', '>=', $monthStart)
            ->with('provider:id,name')
            ->select('provider_id', DB::raw('COUNT(*) as count'))
            ->whereNotNull('provider_id')
            ->groupBy('provider_id')
            ->orderByDesc('count')
            ->first();

        $deliveryRate = ($monthStats->total ?? 0) > 0
            ? round((($monthStats->sent ?? 0) / $monthStats->total) * 100, 1)
            : 0;

        return response()->json(['success' => true, 'data' => [
            'today' => [
                'total' => (int) ($todayStats->total ?? 0),
                'sent' => (int) ($todayStats->sent ?? 0),
                'failed' => (int) ($todayStats->failed ?? 0),
            ],
            'month' => [
                'total' => (int) ($monthStats->total ?? 0),
                'sent' => (int) ($monthStats->sent ?? 0),
                'failed' => (int) ($monthStats->failed ?? 0),
                'pending' => (int) $pending,
                'avg_duration_ms' => (int) round($monthStats->avg_duration ?? 0),
                'delivery_rate' => $deliveryRate,
            ],
            'top_provider' => $topProvider?->provider?->name ?? null,
        ]]);
    }

    public function daily(): JsonResponse
    {
        $days = (int) request('days', 14);

        $rows = SmsLog::whereDate('created_at', '>=', now()->subDays($days)->startOfDay())
            ->selectRaw("DATE(created_at) as date,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed")
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json(['success' => true, 'data' => $rows]);
    }

    public function monthly(): JsonResponse
    {
        $months = (int) request('months', 6);

        $rows = SmsLog::whereDate('created_at', '>=', now()->subMonths($months)->startOfMonth())
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return response()->json(['success' => true, 'data' => $rows]);
    }

    public function failureReasons(): JsonResponse
    {
        $rows = SmsLog::where('status', 'failed')
            ->whereNotNull('failure_reason')
            ->selectRaw("failure_reason, COUNT(*) as count")
            ->groupBy('failure_reason')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        return response()->json(['success' => true, 'data' => $rows]);
    }

    public function providerComparison(): JsonResponse
    {
        $monthStart = now()->startOfMonth();

        $rows = SmsStatistic::where('stat_date', '>=', $monthStart->format('Y-m-d'))
            ->selectRaw("provider_key, SUM(sent) as sent, SUM(failed) as failed, SUM(total_cost) as cost")
            ->groupBy('provider_key')
            ->get();

        return response()->json(['success' => true, 'data' => $rows]);
    }
}
