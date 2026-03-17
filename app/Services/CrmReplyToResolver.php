<?php

namespace App\Services;

use App\Models\ApiIntegrationSetting;
use Illuminate\Support\Facades\Schema;

class CrmReplyToResolver
{
    public static function resolve(): string
    {
        if (Schema::hasTable('api_integration_settings') && Schema::hasColumn('api_integration_settings', 'inbound_mail_username')) {
            $settings = ApiIntegrationSetting::query()->first();
            if ($settings) {
                $inboundUser = trim((string) ($settings->inbound_mail_username ?? ''));
                if ($inboundUser !== '') {
                    return $inboundUser;
                }
            }
        }

        $fallback = trim((string) env('MAIL_FROM_ADDRESS', ''));
        return $fallback !== '' ? $fallback : 'crm@reply.maccento.ca';
    }
}
