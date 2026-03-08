<?php

declare(strict_types=1);

use App\Models\Conversation;
use App\Models\EmailLog;
use App\Models\LeadEvent;
use App\Models\LeadProfile;
use App\Models\Message;
use App\Models\QuoteBuild;
use App\Models\QuoteEvent;
use App\Models\WebsiteFormSubmission;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$explicitEmails = [
    'qa-welcome-test@example.com',
    'qa-welcome-package@example.com',
    'qa-welcome-chat@example.com',
    'qa-welcome-test2@example.com',
    'qa-welcome-package2@example.com',
    'qa-welcome-chat2@example.com',
    'live-contact-qa@example.com',
    'live-package-qa@example.com',
    'live-chat-qa@example.com',
];

$emailPatterns = [
    'qa-%@example.com',
    'live-%@example.com',
];

$report = [];

DB::transaction(function () use ($explicitEmails, $emailPatterns, &$report): void {
    $emailQuery = LeadProfile::query();
    $emailQuery->whereIn('email', $explicitEmails);
    foreach ($emailPatterns as $pattern) {
        $emailQuery->orWhere('email', 'like', $pattern);
    }

    $leadIds = $emailQuery->pluck('id')->all();

    $conversationIds = Conversation::query()
        ->where('channel', 'diagnostic')
        ->orWhere('channel', 'lead_auto_capture')
        ->orWhere('visitor_id', 'like', 'live-chat-qa-%')
        ->orWhereIn('id', LeadProfile::query()->whereIn('id', $leadIds)->whereNotNull('conversation_id')->pluck('conversation_id')->all())
        ->pluck('id')
        ->all();

    $quoteIds = QuoteBuild::query()
        ->whereIn('lead_profile_id', $leadIds)
        ->orWhereIn('conversation_id', $conversationIds)
        ->orWhere(function ($query) use ($explicitEmails, $emailPatterns): void {
            foreach ($explicitEmails as $email) {
                $query->orWhere('options->contact_email', $email);
            }
            foreach ($emailPatterns as $pattern) {
                $query->orWhere('options->contact_email', 'like', $pattern);
            }
        })
        ->pluck('id')
        ->all();

    $report['quote_events'] = QuoteEvent::query()->whereIn('quote_build_id', $quoteIds)->delete();
    $report['quote_builds'] = QuoteBuild::query()->whereIn('id', $quoteIds)->delete();

    $report['lead_events'] = LeadEvent::query()->whereIn('lead_profile_id', $leadIds)->delete();

    $emailLogQuery = EmailLog::query();
    $emailLogQuery->where(function ($query) use ($explicitEmails, $emailPatterns): void {
        $query->whereIn('recipient_email', $explicitEmails);
        foreach ($emailPatterns as $pattern) {
            $query->orWhere('recipient_email', 'like', $pattern);
        }
    });
    $report['email_logs'] = $emailLogQuery->delete();

    $submissionQuery = WebsiteFormSubmission::query();
    $submissionQuery->whereIn('email', $explicitEmails)
        ->orWhere('name', 'like', 'Live % QA%')
        ->orWhere('name', 'like', 'QA Welcome Test%');
    $report['website_form_submissions'] = $submissionQuery->delete();

    $report['messages'] = Message::query()->whereIn('conversation_id', $conversationIds)->delete();
    $report['conversations'] = Conversation::query()->whereIn('id', $conversationIds)->delete();

    $report['lead_profiles'] = LeadProfile::query()->whereIn('id', $leadIds)->delete();
});

echo "Cleanup complete\n";
foreach ($report as $table => $count) {
    echo sprintf("%s: %d\n", $table, (int) $count);
}
