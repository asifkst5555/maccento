<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ClientInvoice;
use App\Models\ClientMessage;
use App\Models\ClientProject;
use App\Models\ClientProjectMedia;
use App\Models\ClientServiceRequest;
use App\Models\Conversation;
use App\Models\LeadProfile;
use App\Models\QuoteBuild;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserPortalPageLoadTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_portal_pages_load_successfully(): void
    {
        Storage::fake('public');

        $user = User::query()->create([
            'name' => 'Client User',
            'email' => 'client@example.com',
            'password' => bcrypt('secret123'),
            'role' => 'client',
            'status' => 'active',
            'phone' => '1234567890',
        ]);

        $client = Client::query()->create([
            'user_id' => $user->id,
            'name' => 'Client User',
            'email' => 'client@example.com',
            'phone' => '1234567890',
            'company' => 'Client Co',
            'status' => 'active',
        ]);

        $project = ClientProject::query()->create([
            'client_id' => $client->id,
            'title' => 'Client Project',
            'service_type' => 'photo,drone',
            'property_address' => '123 Main St',
            'status' => 'accepted',
            'scheduled_at' => now()->addDays(3),
        ]);

        ClientInvoice::query()->create([
            'client_id' => $client->id,
            'client_project_id' => $project->id,
            'created_by' => $user->id,
            'invoice_number' => 'INV-2001',
            'amount' => 300.00,
            'currency' => 'USD',
            'status' => 'paid',
            'issued_at' => now()->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
        ]);

        $quote = QuoteBuild::query()->create([
            'quote_id' => 'Q-TEST-USER',
            'user_id' => $user->id,
            'status' => 'reviewed',
            'listing_type' => 'Residential',
            'services' => ['photo', 'drone'],
            'line_items' => [['label' => 'Photography', 'amount' => 300]],
            'estimated_total' => 300,
            'currency' => 'USD',
            'submitted_at' => now(),
            'options' => [
                'contact_email' => $user->email,
                'contact_phone' => $user->phone,
            ],
        ]);

        ClientMessage::query()->create([
            'client_id' => $client->id,
            'client_project_id' => $project->id,
            'sender_user_id' => $user->id,
            'sender_role' => 'client',
            'message' => 'Client portal message.',
            'sent_at' => now(),
        ]);

        ClientServiceRequest::query()->create([
            'client_id' => $client->id,
            'client_project_id' => $project->id,
            'requester_user_id' => $user->id,
            'requested_service' => 'Twilight photos',
            'status' => 'new',
            'preferred_date' => now()->addDays(7)->toDateString(),
        ]);

        ClientProjectMedia::query()->create([
            'client_project_id' => $project->id,
            'uploaded_by' => $user->id,
            'type' => 'image',
            'disk' => 'public',
            'path' => 'client-projects/sample.jpg',
            'original_name' => 'sample.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 1024,
        ]);
        Storage::disk('public')->put('client-projects/sample.jpg', 'image-bytes');

        ClientProjectMedia::query()->create([
            'client_project_id' => $project->id,
            'uploaded_by' => $user->id,
            'type' => 'final_zip',
            'disk' => 'public',
            'path' => 'client-projects/final.zip',
            'original_name' => 'final.zip',
            'mime_type' => 'application/zip',
            'size_bytes' => 2048,
        ]);
        Storage::disk('public')->put('client-projects/final.zip', 'zip-bytes');

        $conversation = Conversation::query()->create([
            'channel' => 'web',
            'visitor_id' => 'visitor-client',
            'status' => 'active',
            'started_at' => now(),
            'last_message_at' => now(),
        ]);

        LeadProfile::query()->create([
            'conversation_id' => $conversation->id,
            'name' => 'Client Lead',
            'email' => $user->email,
            'phone' => $user->phone,
            'status' => 'new',
            'source' => 'website_contact_form_submission',
        ]);

        $routes = [
            route('user.dashboard'),
            route('user.projects.index'),
            route('user.projects.show', $project),
            route('user.invoices.index'),
            route('user.quotes.index'),
            route('user.messages.index'),
            route('user.deliveries.index'),
            route('user.account.index'),
            route('user.quotes.show', $quote),
        ];

        foreach ($routes as $url) {
            $this
                ->actingAs($user)
                ->get($url)
                ->assertOk();
        }

        $this
            ->actingAs($user)
            ->get(route('user.projects.media.download-zip', $project))
            ->assertOk()
            ->assertDownload('final.zip');
    }
}
