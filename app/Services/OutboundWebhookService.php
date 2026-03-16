<?php

namespace App\Services;

use App\Models\ApiIntegrationSetting;
use App\Models\OutboundWebhookDelivery;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class OutboundWebhookService
{
    /**
     * @param array<string,mixed> $payload
     */
    public function send(string $eventType, array $payload): void
    {
        if (!Schema::hasTable('api_integration_settings')) {
            return;
        }

        $settings = ApiIntegrationSetting::query()->first();
        if (!$settings || !(bool) ($settings->outbound_webhook_enabled ?? false)) {
            return;
        }

        $url = trim((string) ($settings->outbound_webhook_url ?? ''));
        if ($url === '') {
            return;
        }

        $secret = (string) ($settings->outbound_webhook_secret ?? '');
        $idempotencyKey = (string) Str::uuid();
        $timestamp = now()->toIso8601String();

        $body = [
            'event' => $eventType,
            'timestamp' => $timestamp,
            'idempotency_key' => $idempotencyKey,
            'payload' => $payload,
        ];

        $signature = $secret !== '' ? hash_hmac('sha256', json_encode($body), $secret) : null;

        $delivery = null;
        if (Schema::hasTable('outbound_webhook_deliveries')) {
            $delivery = OutboundWebhookDelivery::create([
                'event_type' => $eventType,
                'url' => $url,
                'status' => 'pending',
                'payload' => $body,
            ]);
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders(array_filter([
                    'X-Maccento-Event' => $eventType,
                    'X-Maccento-Timestamp' => $timestamp,
                    'X-Maccento-Idempotency' => $idempotencyKey,
                    'X-Maccento-Signature' => $signature,
                ]))
                ->post($url, $body);

            if ($delivery) {
                $delivery->forceFill([
                    'status' => $response->successful() ? 'delivered' : 'failed',
                    'response_code' => $response->status(),
                    'response_body' => Str::limit((string) $response->body(), 2000),
                    'delivered_at' => now(),
                ])->save();
            }
        } catch (\Throwable $exception) {
            if ($delivery) {
                $delivery->forceFill([
                    'status' => 'failed',
                    'error_message' => Str::limit($exception->getMessage(), 2000),
                    'delivered_at' => now(),
                ])->save();
            }
        }
    }
}
