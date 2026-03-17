<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiIntegrationSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'stripe_publishable_key',
        'stripe_secret_key',
        'paypal_client_id',
        'paypal_secret',
        'paypal_sandbox',
        'mail_mailer',
        'mail_host',
        'mail_port',
        'mail_username',
        'mail_password',
        'mail_encryption',
        'mail_from_address',
        'mail_from_name',
        'inbound_mail_enabled',
        'inbound_mail_provider',
        'inbound_mail_host',
        'inbound_mail_port',
        'inbound_mail_encryption',
        'inbound_mail_username',
        'inbound_mail_password',
        'inbound_mail_mailbox',
        'inbound_mail_search',
        'inbound_mail_max_per_run',
        'inbound_mail_delete_after_process',
        'media_disk',
        's3_key',
        's3_secret',
        's3_region',
        's3_bucket',
        's3_endpoint',
        's3_path_style',
        'outbound_webhook_enabled',
        'outbound_webhook_url',
        'outbound_webhook_secret',
        'chat_provider',
        'chat_api_key',
        'chat_webhook_url',
        'ai_provider',
        'ai_model',
        'openai_api_key',
        'openai_base_url',
        'openrouter_api_key',
        'openrouter_base_url',
        'openrouter_model',
        'gemini_api_key',
        'gemini_base_url',
        'gemini_model',
    ];

    protected $casts = [
        'paypal_sandbox' => 'boolean',
        'mail_port' => 'integer',
        'inbound_mail_enabled' => 'boolean',
        'inbound_mail_port' => 'integer',
        'inbound_mail_max_per_run' => 'integer',
        'inbound_mail_delete_after_process' => 'boolean',
        's3_path_style' => 'boolean',
        'outbound_webhook_enabled' => 'boolean',
    ];
}
