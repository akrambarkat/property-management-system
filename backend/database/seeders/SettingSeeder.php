<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            // General
            ['app_name', 'EMAARPlus', 'general', 'string'],
            ['default_currency', 'ILS', 'general', 'string'],
            ['electricity_unit_price', '0.50', 'general', 'float'],
            ['water_unit_price', '3.00', 'general', 'float'],
            ['invoice_prefix', 'INV-', 'general', 'string'],
            ['contract_prefix', 'CTR-', 'general', 'string'],
            ['receipt_prefix', 'REC-', 'general', 'string'],

            // Company
            ['company_name', 'شركة الإعمار للعقارات', 'company', 'string'],
            ['tax_number', '', 'company', 'string'],
            ['commercial_registration', '', 'company', 'string'],
            ['phone', '', 'company', 'string'],
            ['mobile', '', 'company', 'string'],
            ['email', '', 'company', 'string'],
            ['website', '', 'company', 'string'],
            ['address', '', 'company', 'string'],
            ['city', '', 'company', 'string'],
            ['country', '', 'company', 'string'],
            ['timezone', 'Asia/Jerusalem', 'company', 'string'],
            ['date_format', 'd/m/Y', 'company', 'string'],
            ['language', 'ar', 'company', 'string'],
            ['default_vat', '0', 'company', 'float'],
            ['invoice_footer', '', 'company', 'text'],
            ['invoice_header', '', 'company', 'text'],
            ['payment_terms', 'الدفع خلال 30 يومًا', 'company', 'string'],
            ['logo_path', '', 'company', 'string'],
            ['stamp_path', '', 'company', 'string'],

            // SMS
            ['sms_provider', 'custom', 'sms', 'string'],
            ['sms_api_url', '', 'sms', 'string'],
            ['sms_api_key', '', 'sms', 'string'],
            ['sms_username', '', 'sms', 'string'],
            ['sms_password', '', 'sms', 'string'],
            ['sms_sender_id', '', 'sms', 'string'],
            ['sms_timeout', '15', 'sms', 'integer'],
            ['sms_retries', '3', 'sms', 'integer'],
            ['sms_http_method', 'POST', 'sms', 'string'],
            ['sms_content_type', 'application/json', 'sms', 'string'],
            ['sms_authorization_type', 'bearer', 'sms', 'string'],
            ['sms_custom_headers', '{}', 'sms', 'json'],
            ['sms_enabled', '1', 'sms', 'boolean'],
            ['sms_default_country_code', '970', 'sms', 'string'],

            // Notifications
            ['notify_email', '1', 'notifications', 'boolean'],
            ['notify_sms', '1', 'notifications', 'boolean'],
            ['notify_system', '1', 'notifications', 'boolean'],
            ['notify_on_payment', '1', 'notifications', 'boolean'],
            ['notify_on_contract', '1', 'notifications', 'boolean'],
            ['notify_on_maintenance', '1', 'notifications', 'boolean'],

            // Invoices
            ['invoice_tax_number', '', 'invoices', 'string'],
            ['invoice_due_days', '30', 'invoices', 'integer'],
            ['invoice_late_fee', '0', 'invoices', 'float'],
            ['invoice_show_discount', '1', 'invoices', 'boolean'],
            ['invoice_auto_generate', '1', 'invoices', 'boolean'],

            // Contracts
            ['contract_reminder_days', '30', 'contracts', 'integer'],
            ['contract_auto_renew', '0', 'contracts', 'boolean'],
            ['contract_terms', '', 'contracts', 'text'],

            // Appearance
            ['theme', 'light', 'appearance', 'string'],
            ['app_language', 'ar', 'appearance', 'string'],
            ['compact_mode', '0', 'appearance', 'boolean'],

            // Security
            ['password_min_length', '8', 'security', 'integer'],
            ['session_timeout', '30', 'security', 'integer'],
            ['two_factor', '0', 'security', 'boolean'],
            ['lockout_attempts', '5', 'security', 'integer'],

            // Backup
            ['backup_enabled', '0', 'backup', 'boolean'],
            ['backup_frequency', 'weekly', 'backup', 'string'],
            ['backup_retention_days', '30', 'backup', 'integer'],
            ['backup_destination', 'local', 'backup', 'string'],

            // System
            ['debug_mode', '0', 'system', 'boolean'],
            ['maintenance_mode', '0', 'system', 'boolean'],
            ['max_upload_size', '10', 'system', 'integer'],
            ['log_retention_days', '90', 'system', 'integer'],
        ];

        foreach ($rows as [$key, $value, $group, $type]) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => $group, 'type' => $type]
            );
        }
    }
}
