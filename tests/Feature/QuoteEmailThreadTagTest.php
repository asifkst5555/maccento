<?php

namespace Tests\Feature;

use App\Mail\BrandedNotificationMail;
use App\Models\Client;
use App\Models\ClientProject;
use App\Models\QuoteBuild;
use App\Services\QuoteNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class QuoteEmailThreadTagTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_appends_project_thread_tag_when_quote_is_linked_to_client_project(): void
    {
        putenv('QUOTE_ADMIN_EMAIL=ops@example.com');
        $_ENV['QUOTE_ADMIN_EMAIL'] = 'ops@example.com';
        $_SERVER['QUOTE_ADMIN_EMAIL'] = 'ops@example.com';

        Mail::fake();

        $client = Client::query()->create([
            'name' => 'Alice Client',
            'email' => 'alice@example.com',
            'status' => 'active',
        ]);

        $quote = QuoteBuild::query()->create([
            'quote_id' => 'Q-TEST-1001',
            'status' => 'new',
            'services' => ['photo'],
            'options' => ['contact_name' => 'Alice Client', 'contact_email' => 'alice@example.com'],
            'line_items' => [['label' => 'Photography', 'amount' => 300]],
            'estimated_total' => 300,
            'currency' => 'CAD',
        ]);

        $project = ClientProject::query()->create([
            'client_id' => $client->id,
            'quote_build_id' => $quote->id,
            'title' => 'Downtown Condo',
            'status' => 'accepted',
        ]);

        app(QuoteNotificationService::class)->sendSubmissionEmails($quote);

        Mail::assertSent(BrandedNotificationMail::class, function (BrandedNotificationMail $mail) use ($project): bool {
            if (!$mail->hasTo('alice@example.com')) {
                return false;
            }

            $built = $mail->build();
            return str_contains((string) ($built->subject ?? ''), "[P#{$project->id}]");
        });

        Mail::assertSent(BrandedNotificationMail::class, function (BrandedNotificationMail $mail) use ($project): bool {
            if (!$mail->hasTo('ops@example.com')) {
                return false;
            }

            $built = $mail->build();
            return str_contains((string) ($built->subject ?? ''), "[P#{$project->id}]");
        });
    }

    public function test_it_does_not_append_project_thread_tag_when_quote_has_no_linked_project(): void
    {
        putenv('QUOTE_ADMIN_EMAIL=ops@example.com');
        $_ENV['QUOTE_ADMIN_EMAIL'] = 'ops@example.com';
        $_SERVER['QUOTE_ADMIN_EMAIL'] = 'ops@example.com';

        Mail::fake();

        $quote = QuoteBuild::query()->create([
            'quote_id' => 'Q-TEST-1002',
            'status' => 'new',
            'services' => ['video'],
            'options' => ['contact_name' => 'Bob Client', 'contact_email' => 'bob@example.com'],
            'line_items' => [['label' => 'Video', 'amount' => 600]],
            'estimated_total' => 600,
            'currency' => 'CAD',
        ]);

        app(QuoteNotificationService::class)->sendSubmissionEmails($quote);

        Mail::assertSent(BrandedNotificationMail::class, function (BrandedNotificationMail $mail): bool {
            if (!$mail->hasTo('bob@example.com')) {
                return false;
            }

            $built = $mail->build();
            return !str_contains((string) ($built->subject ?? ''), '[P#');
        });
    }
}
