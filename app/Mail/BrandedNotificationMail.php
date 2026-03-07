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
    ) {
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
                'brandLogoUrl' => rtrim((string) config('app.url', ''), '/') . '/assets/media/logo.png',
            ]);

        if ($this->replyToAddress !== null && trim($this->replyToAddress) !== '') {
            $mail->replyTo($this->replyToAddress);
        }

        if ($this->emailLogId !== null || $this->threadProjectId !== null) {
            $mail->withSymfonyMessage(function (Email $message): void {
                $smtpApiPayload = [
                    'unique_args' => array_filter([
                        'email_log_id' => $this->emailLogId !== null ? (string) $this->emailLogId : null,
                        'client_project_id' => $this->threadProjectId !== null ? (string) $this->threadProjectId : null,
                    ], static fn ($value): bool => $value !== null && $value !== ''),
                    'category' => ['crm_email_center'],
                ];

                $message->getHeaders()->addTextHeader('X-SMTPAPI', (string) json_encode($smtpApiPayload, JSON_UNESCAPED_SLASHES));
            });
        }

        return $mail;
    }
}
