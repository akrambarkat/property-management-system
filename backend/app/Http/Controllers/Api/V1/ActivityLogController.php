<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    /**
     * List activity log entries with search, filtering and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ActivityLog::with('user:id,name')->latest('id');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $logs = $query->paginate((int) $request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $logs->getCollection()->map(fn (ActivityLog $log) => [
                'id' => $log->id,
                'user' => $log->user ? ['id' => $log->user->id, 'name' => $log->user->name] : null,
                'action' => $log->action,
                'description' => $log->description,
                'model_type' => $log->model_type,
                'model_id' => $log->model_id,
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'new_value' => $log->new_value,
                'created_at' => $log->created_at?->toISOString(),
            ]),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    /**
     * Return the distinct action types to drive the filter dropdown.
     */
    public function actions(): JsonResponse
    {
        $actions = ActivityLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action')
            ->all();

        return response()->json(['success' => true, 'data' => $actions]);
    }

    /**
     * Purge activity logs older than the retention window (from settings).
     */
    public function clear(Request $request): JsonResponse
    {
        $days = (int) $request->input('older_than_days', 0);

        $query = ActivityLog::query();
        if ($days > 0) {
            $query->where('created_at', '<', now()->subDays($days));
        }

        $count = $query->delete();

        return response()->json([
            'success' => true,
            'message' => $count > 0 ? "تم حذف {$count} سجل نشاط" : 'لا توجد سجلات للحذف',
            'data' => ['deleted' => $count],
        ]);
    }
}
