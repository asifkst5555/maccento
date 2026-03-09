<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ClientInvoice;
use App\Models\ClientProject;
use App\Models\Conversation;
use App\Models\EmailDraft;
use App\Models\EmailLog;
use App\Models\InboundEmail;
use App\Models\LeadProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPageLoadTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_admin_crm_pages_load_successfully(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('secret123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $client = Client::query()->create([
            'name' => 'Client One',
            'email' => 'client-one@example.com',
            'status' => 'active',
        ]);

        $project = ClientProject::query()->create([
            'client_id' => $client->id,
            'title' => 'Main Project',
            'status' => 'accepted',
        ]);

        ClientInvoice::query()->create([
            'client_id' => $client->id,
            'client_project_id' => $project->id,
            'created_by' => $admin->id,
            'invoice_number' => 'INV-1001',
            'amount' => 250.00,
            'currency' => 'USD',
            'status' => 'sent',
            'issued_at' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
        ]);

        $conversation = Conversation::query()->create([
            'channel' => 'web',
            'visitor_id' => 'visitor-1',
            'status' => 'active',
            'started_at' => now(),
            'last_message_at' => now(),
        ]);

        LeadProfile::query()->create([
            'conversation_id' => $conversation->id,
            'name' => 'Lead One',
            'email' => 'lead-one@example.com',
            'phone' => '1234567890',
            'status' => 'new',
            'source' => 'website_contact_form_submission',
        ]);

        InboundEmail::query()->create([
            'from_email' => 'client-one@example.com',
            'to_email' => 'info@maccento.test',
            'subject' => 'Reply',
            'message_text' => 'Client replied.',
            'stored' => true,
            'received_at' => now(),
        ]);

        EmailLog::query()->create([
            'created_by' => $admin->id,
            'mode' => 'manual',
            'recipient_email' => 'client-one@example.com',
            'subject' => 'Sent Email',
            'body_preview' => 'Preview body',
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        EmailDraft::query()->create([
            'created_by' => $admin->id,
            'recipient_email' => 'client-one@example.com',
            'subject' => 'Draft Email',
            'body' => 'Draft body',
        ]);

        $routes = [
            route('admin.dashboard'),
            route('admin.projects.index'),
            route('admin.media-delivery.index'),
            route('admin.clients.index'),
            route('admin.clients.show', $client),
            route('admin.invoices.index'),
            route('admin.emails.index'),
            route('admin.emails.inbox'),
            route('admin.emails.sent'),
            route('admin.emails.drafts'),
            route('admin.emails.automation.index'),
            route('admin.leads.index'),
            route('admin.quotes.index'),
        ];

        foreach ($routes as $url) {
            $this
                ->actingAs($admin)
                ->get($url)
                ->assertOk();
        }
    }

    public function test_admin_email_inbox_detail_view_loads_successfully(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('secret123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $client = Client::query()->create([
            'name' => 'Client One',
            'email' => 'client-one@example.com',
            'status' => 'active',
        ]);

        $project = ClientProject::query()->create([
            'client_id' => $client->id,
            'title' => 'Main Project',
            'status' => 'accepted',
        ]);

        $inbound = InboundEmail::query()->create([
            'provider' => 'sendgrid',
            'from_email' => 'client-one@example.com',
            'to_email' => 'crm@reply.maccento.ca',
            'subject' => 'Reply [P#' . $project->id . ']',
            'body_text' => 'Client replied.',
            'status' => 'linked',
            'client_id' => $client->id,
            'client_project_id' => $project->id,
            'received_at' => now(),
        ]);

        $this
            ->actingAs($admin)
            ->get(route('admin.emails.inbox', ['open_id' => $inbound->id]))
            ->assertOk()
            ->assertSee('Reply [P#' . $project->id . ']', false)
            ->assertSee('Client replied.', false);
    }
}
