<?php

namespace App\Console\Commands;

use App\Models\ApiIntegrationSetting;
use App\Models\Client;
use App\Models\ClientMessage;
use App\Models\ClientProject;
use App\Models\InboundEmail;
use App\Services\PanelNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class PullInboundEmails extends Command
{
    protected $signature = 'inbound:pull
        {--limit= : Max messages to process in one run}
        {--delete : Delete messages after processing}
        {--dry-run : Connect and parse without writing to DB}';

    protected $description = 'Pull inbound emails from IMAP/POP3 and store them in the CRM inbox.';

    public function handle(): int
    {
        if (!$this->isEnabled()) {
            $this->line('Inbound mail is disabled.');
            return self::SUCCESS;
        }

        if (!function_exists('imap_open')) {
            $this->error('PHP IMAP extension is not enabled. Install/enable ext-imap to pull inbound mail.');
            return self::FAILURE;
        }

        $config = $this->resolveConfig();
        if ($config['host'] === '' || $config['username'] === '' || $config['password'] === '' || $config['port'] === 0) {
            $this->error('Inbound mail configuration is incomplete.');
            return self::FAILURE;
        }

        $mailbox = $this->buildMailboxString($config);
        $this->line('Connecting to inbound mailbox...');

        $connection = @imap_open($mailbox, $config['username'], $config['password'], 0, 1);
        if (!$connection) {
            $this->error('IMAP connection failed: ' . (string) imap_last_error());
            return self::FAILURE;
        }

        $limit = $this->resolveLimit($config);
        $uids = imap_search($connection, $config['search'], SE_UID) ?: [];
        if ($uids === []) {
            $this->line('No inbound messages found.');
            imap_close($connection);
            return self::SUCCESS;
        }

        rsort($uids);
        $uids = array_slice($uids, 0, $limit);
        $stats = [
            'considered' => 0,
            'skipped' => 0,
            'stored' => 0,
            'linked' => 0,
            'failed' => 0,
        ];

        foreach ($uids as $uid) {
            $stats['considered']++;
            try {
                $message = $this->fetchMessage($connection, (int) $uid);
                if ($message === null) {
                    $stats['skipped']++;
                    continue;
                }

                if ($this->alreadyProcessed($message['uid'])) {
                    $stats['skipped']++;
                    continue;
                }

                if ($this->option('dry-run')) {
                    $stats['stored']++;
                    continue;
                }

                $inboundEmail = InboundEmail::create([
                    'provider' => 'cpanel',
                    'from_email' => $message['from_email'],
                    'from_name' => $message['from_name'] !== '' ? $message['from_name'] : null,
                    'to_email' => $message['to_email'] !== '' ? $message['to_email'] : null,
                    'subject' => $message['subject'] !== '' ? $message['subject'] : null,
                    'body_text' => $message['body_text'] !== '' ? $message['body_text'] : null,
                    'body_html' => $message['body_html'] !== '' ? $message['body_html'] : null,
                    'status' => 'received',
                    'raw_headers' => $message['raw_headers'] !== '' ? $message['raw_headers'] : null,
                    'raw_payload' => $message['raw_payload'],
                    'received_at' => $message['received_at'],
                ]);

                $stats['stored']++;

                if ($this->linkInboundEmail($inboundEmail, $message['from_email'], $message['subject'], $message['body_text'])) {
                    $stats['linked']++;
                }

                $this->markProcessed($connection, $message['msgno'], $config['delete_after']);
            } catch (Throwable $exception) {
                $stats['failed']++;
                $this->warn('Failed to process inbound message: ' . $exception->getMessage());
            }
        }

        imap_expunge($connection);
        imap_close($connection);

        $this->newLine();
        $this->info('Inbound pull summary');
        $this->line('Considered: ' . number_format($stats['considered']));
        $this->line('Skipped: ' . number_format($stats['skipped']));
        $this->line('Stored: ' . number_format($stats['stored']));
        $this->line('Linked: ' . number_format($stats['linked']));
        $this->line('Failed: ' . number_format($stats['failed']));

        return $stats['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    public function isEnabled(): bool
    {
        $envEnabled = filter_var(env('INBOUND_MAIL_ENABLED', true), FILTER_VALIDATE_BOOLEAN);

        if (!Schema::hasTable('api_integration_settings')) {
            return $envEnabled;
        }

        $settings = ApiIntegrationSetting::query()->first();
        if (!$settings || !Schema::hasColumn('api_integration_settings', 'inbound_mail_enabled')) {
            return $envEnabled;
        }

        return $settings->inbound_mail_enabled !== null ? (bool) $settings->inbound_mail_enabled : $envEnabled;
    }

    /**
     * @return array{provider:string,host:string,port:int,encryption:string,username:string,password:string,mailbox:string,search:string,delete_after:bool}
     */
    private function resolveConfig(): array
    {
        $config = [
            'provider' => strtolower(trim((string) env('INBOUND_MAIL_PROVIDER', 'imap'))),
            'host' => trim((string) env('INBOUND_MAIL_HOST', '')),
            'port' => (int) env('INBOUND_MAIL_PORT', 0),
            'encryption' => strtolower(trim((string) env('INBOUND_MAIL_ENCRYPTION', 'ssl'))),
            'username' => trim((string) env('INBOUND_MAIL_USERNAME', '')),
            'password' => (string) env('INBOUND_MAIL_PASSWORD', ''),
            'mailbox' => trim((string) env('INBOUND_MAIL_MAILBOX', 'INBOX')),
            'search' => trim((string) env('INBOUND_MAIL_SEARCH', 'UNSEEN')),
            'max_per_run' => (int) env('INBOUND_MAIL_MAX_PER_RUN', 50),
            'delete_after' => filter_var(env('INBOUND_MAIL_DELETE_AFTER_PROCESS', false), FILTER_VALIDATE_BOOLEAN),
        ];

        if (!Schema::hasTable('api_integration_settings')) {
            return $config;
        }

        $settings = ApiIntegrationSetting::query()->first();
        if (!$settings) {
            return $config;
        }

        if (Schema::hasColumn('api_integration_settings', 'inbound_mail_provider')) {
            $config['provider'] = $this->pickSettingValue($settings->inbound_mail_provider, $config['provider']);
        }
        if (Schema::hasColumn('api_integration_settings', 'inbound_mail_host')) {
            $config['host'] = $this->pickSettingValue($settings->inbound_mail_host, $config['host']);
        }
        if (Schema::hasColumn('api_integration_settings', 'inbound_mail_port') && !empty($settings->inbound_mail_port)) {
            $config['port'] = (int) $settings->inbound_mail_port;
        }
        if (Schema::hasColumn('api_integration_settings', 'inbound_mail_encryption')) {
            $config['encryption'] = $this->pickSettingValue($settings->inbound_mail_encryption, $config['encryption']);
        }
        if (Schema::hasColumn('api_integration_settings', 'inbound_mail_username')) {
            $config['username'] = $this->pickSettingValue($settings->inbound_mail_username, $config['username']);
        }
        if (Schema::hasColumn('api_integration_settings', 'inbound_mail_password')) {
            $config['password'] = $this->pickSettingValue($settings->inbound_mail_password, $config['password']);
        }
        if (Schema::hasColumn('api_integration_settings', 'inbound_mail_mailbox')) {
            $config['mailbox'] = $this->pickSettingValue($settings->inbound_mail_mailbox, $config['mailbox']);
        }
        if (Schema::hasColumn('api_integration_settings', 'inbound_mail_search')) {
            $config['search'] = $this->pickSettingValue($settings->inbound_mail_search, $config['search']);
        }
        if (Schema::hasColumn('api_integration_settings', 'inbound_mail_max_per_run') && !empty($settings->inbound_mail_max_per_run)) {
            $config['max_per_run'] = (int) $settings->inbound_mail_max_per_run;
        }
        if (Schema::hasColumn('api_integration_settings', 'inbound_mail_delete_after_process') && $settings->inbound_mail_delete_after_process !== null) {
            $config['delete_after'] = (bool) $settings->inbound_mail_delete_after_process;
        }

        return $config;
    }

    private function pickSettingValue(?string $stored, string $fallback): string
    {
        $stored = trim((string) $stored);
        return $stored !== '' ? $stored : $fallback;
    }

    private function resolveLimit(array $config): int
    {
        $optionLimit = (int) $this->option('limit');
        if ($optionLimit > 0) {
            return $optionLimit;
        }

        if (isset($config['max_per_run']) && (int) $config['max_per_run'] > 0) {
            return (int) $config['max_per_run'];
        }

        $envLimit = (int) env('INBOUND_MAIL_MAX_PER_RUN', 50);
        return $envLimit > 0 ? $envLimit : 50;
    }

    private function buildMailboxString(array $config): string
    {
        $flags = [];
        $provider = $config['provider'] === 'pop3' ? 'pop3' : 'imap';
        $flags[] = $provider;

        if ($config['encryption'] === 'ssl') {
            $flags[] = 'ssl';
        } elseif ($config['encryption'] === 'tls') {
            $flags[] = 'tls';
        }

        $flags[] = 'novalidate-cert';

        $flagString = implode('/', $flags);
        $mailbox = $config['mailbox'] !== '' ? $config['mailbox'] : 'INBOX';

        return sprintf('{%s:%d/%s}%s', $config['host'], $config['port'], $flagString, $mailbox);
    }

    /**
     * @return array{uid:int,msgno:int,from_email:string,from_name:string,to_email:string,subject:string,body_text:string,body_html:string,raw_headers:string,raw_payload:array<string,mixed>,received_at:?Carbon}|null
     */
    private function fetchMessage($connection, int $uid): ?array
    {
        $msgno = imap_msgno($connection, $uid);
        if ($msgno <= 0) {
            return null;
        }

        $header = imap_headerinfo($connection, $msgno);
        $overview = imap_fetch_overview($connection, (string) $msgno, 0);
        $rawHeaders = (string) imap_fetchheader($connection, $msgno);

        $subject = '';
        if (is_array($overview) && isset($overview[0]->subject)) {
            $subject = $this->decodeMimeHeader((string) $overview[0]->subject);
        } elseif (isset($header->subject)) {
            $subject = $this->decodeMimeHeader((string) $header->subject);
        }

        [$fromEmail, $fromName] = $this->extractFromHeader($header);
        $toEmail = $this->extractToHeader($header);

        if ($fromEmail === '') {
            return null;
        }

        $parts = $this->extractBodyParts($connection, $msgno);
        $bodyText = $this->trimQuotedReply($parts['text']);
        if ($bodyText === '' && $parts['html'] !== '') {
            $bodyText = $this->trimQuotedReply(trim((string) preg_replace('/\s+/', ' ', strip_tags($parts['html']))));
        }

        $receivedAt = null;
        if (isset($header->date)) {
            try {
                $receivedAt = Carbon::parse((string) $header->date);
            } catch (Throwable $exception) {
                $receivedAt = null;
            }
        }

        return [
            'uid' => $uid,
            'msgno' => $msgno,
            'from_email' => Str::lower($fromEmail),
            'from_name' => $fromName,
            'to_email' => Str::lower($toEmail),
            'subject' => trim($subject),
            'body_text' => trim($bodyText),
            'body_html' => trim($parts['html']),
            'raw_headers' => trim($rawHeaders),
            'raw_payload' => [
                'uid' => $uid,
                'message_id' => isset($header->message_id) ? (string) $header->message_id : null,
                'overview' => Arr::wrap($overview),
            ],
            'received_at' => $receivedAt,
        ];
    }

    /**
     * @return array{text:string,html:string}
     */
    private function extractBodyParts($connection, int $msgno): array
    {
        $structure = imap_fetchstructure($connection, $msgno);
        if (!$structure) {
            $body = (string) imap_body($connection, $msgno);
            return ['text' => trim($body), 'html' => ''];
        }

        $collector = ['text' => '', 'html' => ''];
        $this->walkStructure($connection, $msgno, $structure, '', $collector);

        return [
            'text' => trim($collector['text']),
            'html' => trim($collector['html']),
        ];
    }

    private function walkStructure($connection, int $msgno, object $structure, string $section, array &$collector): void
    {
        if (isset($structure->parts) && is_array($structure->parts)) {
            foreach ($structure->parts as $index => $part) {
                $partNumber = (string) ($index + 1);
                $partSection = $section === '' ? $partNumber : $section . '.' . $partNumber;
                $this->walkStructure($connection, $msgno, $part, $partSection, $collector);
            }
            return;
        }

        if (($structure->type ?? null) !== 0) {
            return;
        }

        $subtype = strtoupper((string) ($structure->subtype ?? ''));
        $body = (string) imap_fetchbody($connection, $msgno, $section === '' ? '1' : $section);
        $body = $this->decodePartBody($body, (int) ($structure->encoding ?? 0));

        if ($subtype === 'HTML') {
            if ($collector['html'] === '') {
                $collector['html'] = $body;
            }
            return;
        }

        if ($collector['text'] === '') {
            $collector['text'] = $body;
        }
    }

    private function decodePartBody(string $body, int $encoding): string
    {
        return match ($encoding) {
            3 => base64_decode($body, true) ?: '',
            4 => quoted_printable_decode($body),
            default => $body,
        };
    }

    /**
     * @return array{0:string,1:string}
     */
    private function extractFromHeader($header): array
    {
        $fromEmail = '';
        $fromName = '';

        if (isset($header->from) && is_array($header->from) && isset($header->from[0])) {
            $from = $header->from[0];
            $fromEmail = trim((string) ($from->mailbox ?? '')) . '@' . trim((string) ($from->host ?? ''));
            $fromName = isset($from->personal) ? $this->decodeMimeHeader((string) $from->personal) : '';
        } elseif (isset($header->fromaddress)) {
            $fromEmail = $this->extractEmailAddress((string) $header->fromaddress) ?? '';
        }

        $fromEmail = $this->extractEmailAddress($fromEmail) ?? '';
        return [$fromEmail, $fromName];
    }

    private function extractToHeader($header): string
    {
        if (isset($header->to) && is_array($header->to) && isset($header->to[0])) {
            $to = $header->to[0];
            $email = trim((string) ($to->mailbox ?? '')) . '@' . trim((string) ($to->host ?? ''));
            return $this->extractEmailAddress($email) ?? '';
        }

        if (isset($header->toaddress)) {
            return $this->extractEmailAddress((string) $header->toaddress) ?? '';
        }

        return '';
    }

    private function decodeMimeHeader(string $value): string
    {
        $decoded = imap_mime_header_decode($value);
        if (!is_array($decoded)) {
            return trim($value);
        }

        $parts = '';
        foreach ($decoded as $part) {
            $parts .= (string) ($part->text ?? '');
        }

        return trim($parts);
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

    private function alreadyProcessed(int $uid): bool
    {
        return InboundEmail::query()
            ->where('provider', 'cpanel')
            ->where('raw_payload->uid', $uid)
            ->exists();
    }

    private function markProcessed($connection, int $msgno, bool $deleteAfter): void
    {
        if ($deleteAfter) {
            imap_delete($connection, $msgno);
            return;
        }

        imap_setflag_full($connection, (string) $msgno, "\\Seen");
    }

    private function linkInboundEmail(InboundEmail $inboundEmail, string $fromAddress, string $subject, string $messageBody): bool
    {
        $client = Client::query()
            ->whereRaw('LOWER(email) = ?', [Str::lower($fromAddress)])
            ->first();

        if ($client === null) {
            $inboundEmail->status = 'unmatched';
            $inboundEmail->save();
            return false;
        }

        $clientProjectId = $this->resolveClientProjectId($client, $subject, $inboundEmail->raw_headers ?? '');

        $inboundEmail->client_id = $client->id;
        $inboundEmail->client_project_id = $clientProjectId;
        $inboundEmail->status = 'linked';
        $inboundEmail->save();

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

        return true;
    }

    private function resolveClientProjectId(Client $client, string $subject, string $headersBlob): ?int
    {
        $subjectProjectId = $this->extractProjectIdFromText($subject);
        if ($subjectProjectId !== null) {
            return $this->validateClientProjectId($client, $subjectProjectId);
        }

        if (trim($headersBlob) !== '') {
            $headerProjectId = $this->extractProjectIdFromText($headersBlob);
            if ($headerProjectId !== null) {
                return $this->validateClientProjectId($client, $headerProjectId);
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
