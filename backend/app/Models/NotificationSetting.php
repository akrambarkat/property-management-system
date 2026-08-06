<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    protected $fillable = [
        'user_id', 'type', 'is_enabled',
        'in_app_enabled', 'sms_enabled', 'email_enabled',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'in_app_enabled' => 'boolean',
            'sms_enabled' => 'boolean',
            'email_enabled' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function defaultTypes(): array
    {
        return [
            'contract_expiration' => [
                'title' => 'انتهاء العقود',
                'description' => 'إشعارات عند اقتراب انتهاء العقود أو انتهائها',
            ],
            'overdue_invoice' => [
                'title' => 'فواتير متأخرة',
                'description' => 'إشعارات عند تأخر سداد الفواتير',
            ],
            'new_payment' => [
                'title' => 'مدفوعات جديدة',
                'description' => 'إشعارات عند استلام مدفوعات جديدة',
            ],
            'maintenance_request' => [
                'title' => 'طلبات الصيانة',
                'description' => 'إشعارات عند إنشاء أو تحديث طلبات الصيانة',
            ],
            'sms_failure' => [
                'title' => 'فشل إرسال الرسائل',
                'description' => 'إشعارات عند فشل إرسال رسائل SMS',
            ],
            'system_alert' => [
                'title' => 'تنبيهات النظام',
                'description' => 'إشعارات النظام العامة والتنبيهات الأمنية',
            ],
            'login_alert' => [
                'title' => 'تنبيهات تسجيل الدخول',
                'description' => 'إشعارات عند تسجيل الدخول من جهاز جديد',
            ],
        ];
    }
}
