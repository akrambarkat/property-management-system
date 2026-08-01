<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSmsTemplateRequest;
use App\Http\Requests\UpdateSmsTemplateRequest;
use App\Http\Resources\SmsTemplateResource;
use App\Models\SmsTemplate;
use App\Services\ActivityLogService;
use App\Services\SmsTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SmsTemplateController extends Controller
{
    public function __construct(
        private readonly SmsTemplateService $templateService,
        private readonly ActivityLogService $audit,
    ) {
    }

    public function index(): JsonResponse
    {
        $templates = SmsTemplateResource::collection(
            SmsTemplate::orderBy('is_system', 'desc')->orderBy('title')->get()
        );

        return response()->json([
            'success' => true,
            'data' => $templates,
            'variables' => $this->templateService->availableVariables(),
        ]);
    }

    public function store(StoreSmsTemplateRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['key'] = $data['key'] ?? 'custom_' . Str::slug($data['title'] . '_' . Str::random(4));
        $data['variables'] = $data['variables'] ?? array_keys($this->templateService->availableVariables());
        $data['is_active'] = $data['is_active'] ?? true;

        $template = SmsTemplate::create($data);
        $this->audit->log('sms.template.created', $template, null, "إنشاء قالب SMS: {$template->title}");

        return response()->json(['success' => true, 'message' => 'تم إنشاء القالب', 'data' => new SmsTemplateResource($template)], 201);
    }

    public function show(SmsTemplate $template): JsonResponse
    {
        return response()->json(['success' => true, 'data' => new SmsTemplateResource($template)]);
    }

    public function update(UpdateSmsTemplateRequest $request, SmsTemplate $template): JsonResponse
    {
        $data = $request->validated();
        $old = $template->only(['title', 'message']);
        $template->update($data);
        $this->audit->log('sms.template.updated', $template, ['old' => $old, 'new' => $data], "تعديل قالب SMS: {$template->title}");

        return response()->json(['success' => true, 'message' => 'تم تعديل القالب', 'data' => new SmsTemplateResource($template)]);
    }

    public function destroy(SmsTemplate $template): JsonResponse
    {
        if ($template->is_system) {
            return response()->json(['success' => false, 'message' => 'لا يمكن حذف قالب النظام'], 422);
        }
        $this->audit->log('sms.template.deleted', $template, null, "حذف قالب SMS: {$template->title}");
        $template->delete();

        return response()->json(['success' => true, 'message' => 'تم حذف القالب']);
    }

    public function preview(Request $request): JsonResponse
    {
        $message = $request->input('message', '');
        $data = $request->input('data', []);
        return response()->json([
            'success' => true,
            'data' => ['rendered' => $this->templateService->render($message, $data)],
        ]);
    }

    public function toggle(Request $request, SmsTemplate $template): JsonResponse
    {
        $template->update(['is_active' => !$template->is_active]);
        $this->audit->log('sms.template.toggled', $template, null, ($template->is_active ? 'تفعيل' : 'تعطيل') . " قالب SMS: {$template->title}");
        return response()->json(['success' => true, 'message' => $template->is_active ? 'تم تفعيل القالب' : 'تم تعطيل القالب', 'data' => new SmsTemplateResource($template)]);
    }
}
