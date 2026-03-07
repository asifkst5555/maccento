<?php

namespace App\Jobs;

use App\Models\LeadProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendLeadNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private readonly int $leadProfileId)
    {
    }

    public function handle(): void
    {
        $leadProfile = LeadProfile::find($this->leadProfileId);
        if (!$leadProfile) {
            return;
        }

        $recipient = (string) config('mail.lead_alert_address');
        if ($recipient === '') {
            return;
        }

        $subject = sprintf('Qualified Lead #%d', $leadProfile->id);
        $body = sprintf(
            "Lead qualified.\nName: %s\nEmail: %s\nPhone: %s\nService: %s\nLocation: %s\nTimeline: %s\nScore: %d",
            $leadProfile->name ?? '-',
            $leadProfile->email ?? '-',
            $leadProfile->phone ?? '-',
            $leadProfile->service_type ?? '-',
            $leadProfile->location ?? '-',
            $leadProfile->timeline ?? '-',
            $leadProfile->score ?? 0,
        );

        Mail::raw($body, static function ($message) use ($recipient, $subject): void {
            $message->to($recipient)->subject($subject);
        });
    }
}
