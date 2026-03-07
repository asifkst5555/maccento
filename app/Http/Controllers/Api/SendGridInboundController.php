<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientMessage;
use App\Models\ClientProject;
use App\Services\PanelNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SendGridInboundController extends Controller
{
    public function parse(Request $request): JsonResponse
    {
        if (!$this->isAuthorized($request)) {
            return response()->json(['ok' => false, 'message' => 'Unauthorized inbound webhook request.'], 401);
        }

        $fromAddress = $this->extractEmailAddress((string) $request->input('from', ''));
        if ($fromAddress === null) {
            return response()->json(['ok' => false, 'message' => 'Missing sender address.'], 422);
        }

        $client = Client::query()
            ->whereRaw('LOWER(email) = ?', [Str::lower($fromAddress)])
            ->first();

        if ($client === null) {
            return response()->json([
                'ok' => true,
                'stored' => false,
                'message' => 'No CRM client mapped to sender email.',
            ]);
        }

        $subject = trim((string) $request->input('subject', ''));
        $messageBody = $this->buildMessageBody($request);
        $clientProjectId = $this->resolveClientProjectId($request, $client, $subject);

        if ($messageBody === '') {
            return response()->json(['ok' => false, 'message' => 'Empty inbound message.'], 422);
        }

        $timelineMessage = $subject !== ''
            ? "Subject: {$subject}\n\n{$messageBody}"
            : $messageBody;

        $message = ClientMessage::create([
            'client_id' => $client->id,
            'client_project_id' => $clientProjectId,
            'sender_user_id' => null,
            'sender_role' => 'client',
            'message' => $timelineMessage,
            'sent_at' => now(),
        ]);

        app(PanelNotificationService::class)->notifyInternal(
            'client_email_reply_received',
            'Client email reply received',
            mb_strimwidth("{$client->name}: {$subject}", 0, 140, '...'),
            route('admin.clients.show', $client),
            [
                'client_id' => $client->id,
                'client_project_id' => $clientProjectId,
                'client_message_id' => $message->id,
                'from' => $fromAddress,
                'subject' => $subject,
            ]
        );

        return response()->json([
            'ok' => true,
            'stored' => true,
            'client_id' => $client->id,
            'client_project_id' => $clientProjectId,
            'client_message_id' => $message->id,
        ]);
    }

    private function resolveClientProjectId(Request $request, Client $client, string $subject): ?int
    {
        $subjectProjectId = $this->extractProjectIdFromText($subject);
        if ($subjectProjectId !== null) {
            return $this->validateClientProjectId($client, $subjectProjectId);
        }

        $headersBlob = trim((string) $request->input('headers', ''));
        if ($headersBlob !== '') {
            $headerProjectId = $this->extractProjectIdFromText($headersBlob);
            if ($headerProjectId !== null) {
                return $this->validateClientProjectId($client, $headerProjectId);
            }
        }

        $inReplyTo = trim((string) $request->header('In-Reply-To', ''));
        if ($inReplyTo !== '') {
            $inReplyProjectId = $this->extractProjectIdFromText($inReplyTo);
            if ($inReplyProjectId !== null) {
                return $this->validateClientProjectId($client, $inReplyProjectId);
            }
        }

        $references = trim((string) $request->header('References', ''));
        if ($references !== '') {
            $referencesProjectId = $this->extractProjectIdFromText($references);
            if ($referencesProjectId !== null) {
                return $this->validateClientProjectId($client, $referencesProjectId);
            }
        }

        return null;
    }

    private function validateClientProjectId(Client $client, int $projectId): ?int
    {
        $resolved = ClientProject::query()
            ->where('client_id', $client->id)
            ->where('id', $projectId)
            ->value('id');

        return $resolved !== null ? (int) $resolved : null;
    }

    private function extractProjectIdFromText(string $value): ?int
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $patterns = [
            '/\[(?:project|proj|p)\s*[-:#]?\s*(\d+)\]/i',
            '/(?:project|proj|p)\s*[-:#]\s*(\d+)/i',
            '/\bp(\d+)\b/i',
            '/\bcp[-_#:]?(\d+)\b/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value, $matches) === 1) {
                $candidate = (int) ($matches[1] ?? 0);
                return $candidate > 0 ? $candidate : null;
            }
        }

        return null;
    }

    private function isAuthorized(Request $request): bool
    {
        $expectedToken = trim((string) env('SENDGRID_INBOUND_WEBHOOK_TOKEN', ''));
        if ($expectedToken === '') {
            return false;
        }

        $candidate = (string) ($request->bearerToken()
            ?? $request->header('X-Inbound-Webhook-Token')
            ?? $request->query('token', ''));

        if ($candidate === '') {
            return false;
        }

        return hash_equals($expectedToken, trim($candidate));
    }

    private function extractEmailAddress(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        if (preg_match('/<([^>]+)>/', $raw, $matches) === 1) {
            $raw = trim((string) $matches[1]);
        }

        $email = filter_var($raw, FILTER_VALIDATE_EMAIL);
        return $email !== false ? Str::lower((string) $email) : null;
    }

    private function buildMessageBody(Request $request): string
    {
        $text = trim((string) $request->input('text', ''));
        if ($text !== '') {
            return $this->trimQuotedReply($text);
        }

        $html = trim((string) $request->input('html', ''));
        if ($html === '') {
            return '';
        }

        $plain = trim((string) preg_replace('/\s+/', ' ', strip_tags($html)));
        return $this->trimQuotedReply($plain);
    }

    private function trimQuotedReply(string $body): string
    {
        $separators = [
            "\nOn ",
            "\nFrom:",
            "\n-----Original Message-----",
        ];

        $trimmed = $body;
        foreach ($separators as $separator) {
            $position = mb_stripos($trimmed, $separator);
            if ($position !== false) {
                $trimmed = trim(mb_substr($trimmed, 0, $position));
            }
        }

        return trim($trimmed);
    }
}
