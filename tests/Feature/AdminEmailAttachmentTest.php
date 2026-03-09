<?php

namespace Tests\Feature;

use App\Mail\BrandedNotificationMail;
use App\Models\EmailDraft;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminEmailAttachmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_send_compose_email_with_attachments(): void
    {
        Mail::fake();

        $admin = User::query()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('secret123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $attachment = UploadedFile::fake()->create('brochure.pdf', 128, 'application/pdf');

        $this
            ->actingAs($admin)
            ->post(route('admin.emails.send'), [
                'mode' => 'custom',
                'recipient_email' => 'client@example.com',
                'subject' => 'Project brochure',
                'message' => 'Please find the attached brochure.',
                'attachments' => [$attachment],
            ])
            ->assertRedirect(route('admin.emails.sent'));

        Mail::assertSent(BrandedNotificationMail::class, function (BrandedNotificationMail $mail): bool {
            if (!$mail->hasTo('client@example.com')) {
                return false;
            }

            $attachments = $mail->outboundAttachments();

            return count($attachments) === 1
                && ($attachments[0]['name'] ?? null) === 'brochure.pdf';
        });
    }

    public function test_sending_from_compose_marks_existing_draft_as_sent(): void
    {
        Mail::fake();

        $admin = User::query()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('secret123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $draft = EmailDraft::query()->create([
            'created_by' => $admin->id,
            'recipient_email' => 'client@example.com',
            'reply_to' => 'crm@reply.maccento.ca',
            'subject' => 'Follow-up',
            'message' => 'Checking in with you.',
            'status' => 'draft',
        ]);

        $this
            ->actingAs($admin)
            ->post(route('admin.emails.send'), [
                'draft_id' => $draft->id,
                'mode' => 'custom',
                'recipient_email' => 'client@example.com',
                'subject' => 'Follow-up',
                'message' => 'Checking in with you.',
            ])
            ->assertRedirect(route('admin.emails.sent'));

        $draft->refresh();

        $this->assertSame('sent', $draft->status);
    }
}
