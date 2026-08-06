<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(private readonly NotificationService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $query = Notification::forUser($request->user()->id)
            ->notArchived()
            ->with('creator:id,name');

        if ($request->has('is_read')) {
            $query->where('is_read', $request->boolean('is_read'));
        }

        if ($request->filled('type')) {
            $query->byType($request->type);
        }

        if ($request->filled('priority')) {
            $query->byPriority($request->priority);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%");
            });
        }

        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->to);
        }

        $notifications = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json(['success' => true, 'data' => $notifications]);
    }

    public function latest(Request $request): JsonResponse
    {
        $notifications = $this->service->getLatest($request->user()->id, 10);
        return response()->json(['success' => true, 'data' => $notifications]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $count = $this->service->getUnreadCount($request->user()->id);
        return response()->json(['success' => true, 'data' => ['count' => $count]]);
    }

    public function markAsRead(int $id, Request $request): JsonResponse
    {
        $result = $this->service->markAsRead($id, $request->user()->id);
        if (!$result) {
            return response()->json(['success' => false, 'message' => 'الإشعار غير موجود'], 404);
        }
        return response()->json(['success' => true, 'message' => 'تم تحديد الإشعار كمقروء']);
    }

    public function markAsUnread(int $id, Request $request): JsonResponse
    {
        $notification = Notification::where('id', $id)->where('user_id', $request->user()->id)->first();
        if (!$notification) {
            return response()->json(['success' => false, 'message' => 'الإشعار غير موجود'], 404);
        }
        $notification->markAsUnread();
        return response()->json(['success' => true, 'message' => 'تم تحديد الإشعار كغير مقروء']);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $count = $this->service->markAllAsRead($request->user()->id);
        return response()->json(['success' => true, 'message' => "تم تحديد {$count} إشعارات كمقروءة"]);
    }

    public function archive(int $id, Request $request): JsonResponse
    {
        $result = $this->service->archive($id, $request->user()->id);
        if (!$result) {
            return response()->json(['success' => false, 'message' => 'الإشعار غير موجود'], 404);
        }
        return response()->json(['success' => true, 'message' => 'تم أرشفة الإشعار']);
    }

    public function destroy(int $id, Request $request): JsonResponse
    {
        $result = $this->service->delete($id, $request->user()->id);
        if (!$result) {
            return response()->json(['success' => false, 'message' => 'الإشعار غير موجود'], 404);
        }
        return response()->json(['success' => true, 'message' => 'تم حذف الإشعار']);
    }

    public function bulkAction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:notifications,id',
            'action' => 'required|in:read,unread,archive,delete',
        ]);

        $userId = $request->user()->id;
        $ids = $validated['ids'];

        $result = match ($validated['action']) {
            'read' => $this->service->bulkMarkAsRead($ids, $userId),
            'archive' => $this->service->bulkArchive($ids, $userId),
            'delete' => $this->service->bulkDelete($ids, $userId),
            'unread' => Notification::whereIn('id', $ids)
                ->where('user_id', $userId)
                ->update(['is_read' => false, 'read_at' => null]),
        };

        $messages = [
            'read' => "تم تحديد {$result} إشعارات كمقروءة",
            'unread' => "تم تحديد {$result} إشعارات كغير مقروءة",
            'archive' => "تم أرشفة {$result} إشعارات",
            'delete' => "تم حذف {$result} إشعارات",
        ];

        return response()->json(['success' => true, 'message' => $messages[$validated['action']]]);
    }

    public function getSettings(Request $request): JsonResponse
    {
        $settings = $this->service->getUserSettings($request->user()->id);
        return response()->json(['success' => true, 'data' => $settings->values()]);
    }

    public function updateSetting(Request $request, string $type): JsonResponse
    {
        $validated = $request->validate([
            'is_enabled' => 'boolean',
            'in_app_enabled' => 'boolean',
            'sms_enabled' => 'boolean',
            'email_enabled' => 'boolean',
        ]);

        $setting = $this->service->updateUserSetting($request->user()->id, $type, $validated);
        return response()->json(['success' => true, 'message' => 'تم تحديث الإعدادات', 'data' => $setting]);
    }

    public function check(Request $request): JsonResponse
    {
        $this->service->checkExpiringContracts(30);
        $this->service->checkExpiredContracts();
        $this->service->checkOverdueInvoices();

        return response()->json(['success' => true, 'message' => 'تم فحص الإشعارات التلقائية']);
    }
}
