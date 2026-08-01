<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SmsJob;
use App\Models\SmsTemplate;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SmsJobController extends Controller
{
    public function __construct(private readonly ActivityLogService $audit)
    {
    }

    public function index(): JsonResponse
    {
        $jobs = SmsJob::with('template:id,title')->orderBy('name')->get();
        return response()->json(['success' => true, 'data' => $jobs]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'event_type' => 'required|string|max:50',
            'template_id' => 'required|exists:sms_templates,id',
            'days_before' => 'nullable|integer|min:0|max:365',
            'conditions' => 'nullable|array',
            'recipient_scope' => 'nullable|string|max:50',
            'building_id' => 'nullable|exists:buildings,id',
            'is_active' => 'nullable|boolean',
        ]);

        $job = SmsJob::create(array_merge($validated, ['is_active' => $validated['is_active'] ?? true]));
        $this->audit->log('sms.job.created', $job, null, "إنشاء قاعدة إرسال: {$job->name}");

        return response()->json(['success' => true, 'message' => 'تم إنشاء قاعدة الإرسال', 'data' => $job->load('template:id,title')], 201);
    }

    public function update(Request $request, SmsJob $job): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:191',
            'event_type' => 'sometimes|string|max:50',
            'template_id' => 'sometimes|exists:sms_templates,id',
            'days_before' => 'nullable|integer|min:0|max:365',
            'conditions' => 'nullable|array',
            'recipient_scope' => 'nullable|string|max:50',
            'building_id' => 'nullable|exists:buildings,id',
            'is_active' => 'nullable|boolean',
        ]);

        $job->update($validated);
        $this->audit->log('sms.job.updated', $job, ['new' => $validated], "تعديل قاعدة إرسال: {$job->name}");

        return response()->json(['success' => true, 'message' => 'تم تحديث قاعدة الإرسال', 'data' => $job->load('template:id,title')]);
    }

    public function destroy(SmsJob $job): JsonResponse
    {
        $this->audit->log('sms.job.deleted', $job, null, "حذف قاعدة إرسال: {$job->name}");
        $job->delete();
        return response()->json(['success' => true, 'message' => 'تم حذف قاعدة الإرسال']);
    }

    public function toggle(SmsJob $job): JsonResponse
    {
        $job->update(['is_active' => !$job->is_active]);
        return response()->json(['success' => true, 'message' => $job->is_active ? 'تم التفعيل' : 'تم التعطيل', 'data' => $job]);
    }
}
