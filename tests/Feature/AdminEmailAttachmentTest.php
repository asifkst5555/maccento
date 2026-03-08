<?php

namespace Tests\Feature;

use App\Mail\BrandedNotificationMail;
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
}
