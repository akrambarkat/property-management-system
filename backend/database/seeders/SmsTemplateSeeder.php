<?php

namespace Database\Seeders;

use App\Models\SmsTemplate;
use Illuminate\Database\Seeder;

class SmsTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'key' => 'rent_reminder',
                'title' => 'تذكير بدفع الإيجار',
                'subject' => 'تذكير بدفع الإيجار',
                'message' => "عزيزي {{tenant_name}}،\nنذكّركم باستحقاق إيجار الوحدة {{unit}} بتاريخ {{due_date}} بمبلغ {{amount}}. يرجى تسوية المبلغ قبل التاريخ المذكور.\n{{company}}",
                'variables' => ['tenant_name', 'unit', 'amount', 'due_date', 'company'],
                'is_system' => true,
            ],
            [
                'key' => 'invoice_created',
                'title' => 'فاتورة جديدة',
                'subject' => 'تم إصدار فاتورة',
                'message' => "عزيزي {{tenant_name}}،\nتم إصدار فاتورة رقم {{invoice_number}} بمبلغ {{amount}} تستحق بتاريخ {{due_date}}.\n{{company}}",
                'variables' => ['tenant_name', 'invoice_number', 'amount', 'due_date', 'company'],
                'is_system' => true,
            ],
            [
                'key' => 'payment_received',
                'title' => 'تأكيد استلام دفعة',
                'subject' => 'تم استلام دفعتك',
                'message' => "عزيزي {{tenant_name}}،\nتم استلام دفعتك بتاريخ {{payment_date}} بمبلغ {{amount}} للفاتورة {{invoice_number}}. شكرًا لالتزامكم.\n{{company}}",
                'variables' => ['tenant_name', 'payment_date', 'amount', 'invoice_number', 'company'],
                'is_system' => true,
            ],
            [
                'key' => 'contract_expiring',
                'title' => 'اقتراب انتهاء العقد',
                'subject' => 'انتهاء عقد الإيجار',
                'message' => "عزيزي {{tenant_name}}،\nنود إعلامكم بأن عقد إيجار الوحدة {{unit}} سينتهي بتاريخ {{contract_end}}. يُرجى التواصل معنا لتجديد العقد.\n{{company}}",
                'variables' => ['tenant_name', 'unit', 'contract_end', 'company'],
                'is_system' => true,
            ],
            [
                'key' => 'maintenance_update',
                'title' => 'تحديث طلب صيانة',
                'subject' => 'تحديث حالة طلب الصيانة',
                'message' => "عزيزي {{tenant_name}}،\nتم تحديث حالة طلب الصيانة الخاص بالوحدة {{unit}}. لمزيد من التفاصيل تواصل معنا.\n{{company}}",
                'variables' => ['tenant_name', 'unit', 'company'],
                'is_system' => true,
            ],
            [
                'key' => 'password_reset',
                'title' => 'إعادة تعيين كلمة المرور',
                'subject' => 'إعادة تعيين كلمة المرور',
                'message' => "عزيزي {{tenant_name}}،\nتم إعادة تعيين كلمة المرور لحسابك. إذا لم تقم بذلك، يرجى التواصل معنا فورًا.\n{{company}}",
                'variables' => ['tenant_name', 'company'],
                'is_system' => true,
            ],
            [
                'key' => 'welcome_message',
                'title' => 'رسالة ترحيب',
                'subject' => 'مرحبًا بك',
                'message' => "أهلًا بك {{tenant_name}} في {{property}}!\nيسعدنا انضمامك إلينا. لمزيد من المعلومات تواصل معنا على {{phone}} أو {{website}}.\n{{company}}",
                'variables' => ['tenant_name', 'property', 'phone', 'website', 'company'],
                'is_system' => true,
            ],
        ];

        foreach ($templates as $template) {
            SmsTemplate::updateOrCreate(
                ['key' => $template['key']],
                $template,
            );
        }
    }
}
