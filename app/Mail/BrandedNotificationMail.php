<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Mime\Email;

class BrandedNotificationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param array<int,string> $bodyLines
     */
    public function __construct(
        private readonly string $subjectLine,
        private readonly string $heading,
        private readonly array $bodyLines,
        private readonly ?string $intro = null,
        private readonly ?string $ctaLabel = null,
        private readonly ?string $ctaUrl = null,
        private readonly ?string $footerNote = null,
        private readonly ?int $emailLogId = null,
        private readonly ?int $threadProjectId = null,
        private readonly ?string $replyToAddress = null,
        /** @var array<int,array{path:string,name:string,mime:?string}> */
        private readonly array $outboundAttachmentMeta = [],
    ) {
    }

    /**
     * @return array<int,array{path:string,name:string,mime:?string}>
     */
    public function outboundAttachments(): array
    {
        return $this->outboundAttachmentMeta;
    }

    public function build(): self
    {
        $mail = $this
            ->subject($this->subjectLine)
            ->view('emails.branded-notification')
            ->text('emails.branded-notification-text')
            ->with([
                'subjectLine' => $this->subjectLine,
                'heading' => $this->heading,
                'intro' => $this->intro,
                'bodyLines' => $this->bodyLines,
                'ctaLabel' => $this->ctaLabel,
                'ctaUrl' => $this->ctaUrl,
                'footerNote' => $this->footerNote,
                'brandName' => (string) config('app.name', 'Maccento'),
                'brandLogoUrl' => $this->resolveBrandLogoUrl(),
            ]);

        if ($this->replyToAddress !== null && trim($this->replyToAddress) !== '') {
            $mail->replyTo($this->replyToAddress);
        }

        foreach ($this->outboundAttachmentMeta as $attachment) {
            $path = (string) ($attachment['path'] ?? '');
            if ($path === '' || !is_file($path)) {
                continue;
            }

            $options = [
                'as' => (string) ($attachment['name'] ?? basename($path)),
            ];

            $mime = isset($attachment['mime']) ? trim((string) $attachment['mime']) : '';
            if ($mime !== '') {
                $options['mime'] = $mime;
            }

            $mail->attach($path, $options);
        }

        $mail->withSymfonyMessage(function (Email $message): void {
            if ($this->emailLogId !== null || $this->threadProjectId !== null) {
                $smtpApiPayload = [
                    'unique_args' => array_filter([
                        'email_log_id' => $this->emailLogId !== null ? (string) $this->emailLogId : null,
                        'client_project_id' => $this->threadProjectId !== null ? (string) $this->threadProjectId : null,
                    ], static fn ($value): bool => $value !== null && $value !== ''),
                    'category' => ['crm_email_center'],
                ];

                $message->getHeaders()->addTextHeader('X-SMTPAPI', (string) json_encode($smtpApiPayload, JSON_UNESCAPED_SLASHES));
            }
        });

        return $mail;
    }

    private function resolveBrandLogoUrl(): string
    {
        $appUrl = trim((string) config('app.url', ''));
        $fallbackBase = trim((string) config('maccento_bot.company.website_url', 'https://maccento.ca'));
        $baseUrl = $this->isLocalUrl($appUrl) ? $fallbackBase : $appUrl;

        if ($baseUrl === '') {
            $baseUrl = 'https://maccento.ca';
        }

        return rtrim($baseUrl, '/') . '/assets/media/logo-footer.png';
    }

    private function isLocalUrl(string $url): bool
    {
        if ($url === '') {
            return true;
        }

        $host = (string) parse_url($url, PHP_URL_HOST);
        $host = strtolower(trim($host));

        return in_array($host, ['', '127.0.0.1', 'localhost', '::1'], true);
    }
}
