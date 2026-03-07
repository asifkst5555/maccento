<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmailLog;
use App\Models\SendgridWebhookEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class SendGridWebhookController extends Controller
{
    public function events(Request $request): JsonResponse
    {
        $rawBody = $request->getContent();
        $signature = (string) $request->header('X-Twilio-Email-Event-Webhook-Signature', '');
        $timestamp = (string) $request->header('X-Twilio-Email-Event-Webhook-Timestamp', '');

        if (!$this->verifyWebhookSignature($timestamp, $rawBody, $signature)) {
            return response()->json(['ok' => false, 'message' => 'Invalid webhook signature.'], 401);
        }

        $events = json_decode($rawBody, true);
        if (!is_array($events)) {
            return response()->json(['ok' => false, 'message' => 'Invalid payload format.'], 422);
        }

        $stored = 0;
        foreach ($events as $event) {
            if (!is_array($event)) {
                continue;
            }

            $emailLogIdRaw = data_get($event, 'unique_args.email_log_id', data_get($event, 'custom_args.email_log_id', data_get($event, 'email_log_id')));
            $emailLogId = is_numeric($emailLogIdRaw) ? (int) $emailLogIdRaw : null;
            $eventType = (string) data_get($event, 'event', 'unknown');
            $sgMessageId = data_get($event, 'sg_message_id') ? (string) data_get($event, 'sg_message_id') : null;

            $occurredAt = null;
            $eventTimestamp = data_get($event, 'timestamp');
            if (is_numeric($eventTimestamp)) {
                $occurredAt = Carbon::createFromTimestampUTC((int) $eventTimestamp);
            }

            SendgridWebhookEvent::create([
                'email_log_id' => $emailLogId,
                'event_type' => $eventType,
                'email' => data_get($event, 'email') ? (string) data_get($event, 'email') : null,
                'sg_message_id' => $sgMessageId,
                'sg_event_id' => data_get($event, 'sg_event_id') ? (string) data_get($event, 'sg_event_id') : null,
                'category' => is_array(data_get($event, 'category')) ? data_get($event, 'category') : null,
                'payload' => $event,
                'occurred_at' => $occurredAt,
                'processed_at' => now(),
            ]);

            if ($emailLogId !== null) {
                $emailLog = EmailLog::query()->find($emailLogId);
                if ($emailLog) {
                    $shouldUpdate = $emailLog->provider_last_event_at === null
                        || $occurredAt === null
                        || $occurredAt->greaterThanOrEqualTo($emailLog->provider_last_event_at);

                    if ($shouldUpdate) {
                        $emailLog->provider_status = $eventType;
                        $emailLog->provider_last_event_at = $occurredAt ?? now();
                        if ($sgMessageId !== null && $emailLog->provider_message_id === null) {
                            $emailLog->provider_message_id = $sgMessageId;
                        }

                        if (in_array($eventType, ['bounce', 'dropped', 'blocked', 'spamreport'], true)) {
                            $emailLog->status = 'failed';
                            $emailLog->error_message = (string) data_get($event, 'reason', data_get($event, 'response', $emailLog->error_message));
                        }

                        if (in_array($eventType, ['delivered', 'open', 'click'], true) && $emailLog->status !== 'failed') {
                            $emailLog->status = 'sent';
                        }

                        $emailLog->save();
                    }
                }
            }

            $stored++;
        }

        return response()->json(['ok' => true, 'stored' => $stored]);
    }

    private function verifyWebhookSignature(string $timestamp, string $rawBody, string $signature): bool
    {
        $publicKey = trim((string) env('SENDGRID_WEBHOOK_SIGNING_KEY', ''));
        if ($publicKey === '') {
            return false;
        }

        if ($timestamp === '' || $signature === '') {
            return false;
        }

        if (!function_exists('sodium_crypto_sign_verify_detached')) {
            return false;
        }

        $decodedPublicKey = base64_decode($publicKey, true);
        $decodedSignature = base64_decode($signature, true);

        if ($decodedPublicKey === false || $decodedSignature === false) {
            return false;
        }

        return sodium_crypto_sign_verify_detached(
            $decodedSignature,
            $timestamp . $rawBody,
            $decodedPublicKey,
        );
    }
}
