<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\ClientInvoice;
use App\Models\ClientMessage;
use App\Models\ClientProject;
use App\Models\ClientServiceRequest;
use App\Models\Conversation;
use App\Models\FollowUp;
use App\Models\LeadEvent;
use App\Models\LeadProfile;
use App\Models\Message;
use App\Models\PanelNotification;
use App\Models\QuoteBuild;
use App\Models\QuoteEvent;
use App\Models\User;
use Illuminate\Database\Seeder;

class CleanDemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@maccento.com'],
            [
                'name' => 'Admin',
                'phone' => '+15140000001',
                'role' => 'admin',
                'password' => 'admin@1234',
            ]
        );
        $admin->forceFill([
            'name' => 'Admin',
            'phone' => '+15140000001',
            'role' => 'admin',
            'password' => 'admin@1234',
        ])->save();

        $clientUser = User::query()->firstOrCreate(
            ['email' => 'client.demo@maccento.com'],
            [
                'name' => 'Demo Client',
                'phone' => '+15145550001',
                'role' => 'client',
                'password' => 'demo@1234',
            ]
        );
        $clientUser->forceFill([
            'name' => 'Demo Client',
            'phone' => '+15145550001',
            'role' => 'client',
            'password' => 'demo@1234',
        ])->save();

        $client = Client::query()->firstOrCreate(
            ['email' => 'client.demo@maccento.com'],
            [
                'user_id' => $clientUser->id,
                'created_by' => $admin->id,
                'name' => 'Demo Client',
                'phone' => '+15145550001',
                'company' => 'Demo Realty',
                'status' => 'active',
                'notes' => 'Minimal demo client.',
            ]
        );

        $conversation = Conversation::query()->firstOrCreate(
            ['visitor_id' => 'clean-demo-visitor'],
            [
                'channel' => 'website_widget',
                'status' => 'active',
                'started_at' => now()->subDay(),
                'last_message_at' => now()->subHours(2),
                'metadata' => ['language' => 'en', 'seeded' => 'clean_demo'],
            ]
        );

        $lead = LeadProfile::query()->firstOrCreate(
            ['conversation_id' => $conversation->id],
            [
                'name' => 'Alex Demo',
                'email' => 'alex.demo.lead@example.com',
                'phone' => '+15147770001',
                'service_type' => 'photo,drone',
                'property_type' => 'condo',
                'location' => 'Montreal',
                'timeline' => 'this week',
                'score' => 65,
                'status' => 'new',
                'notes' => 'Clean demo lead.',
            ]
        );

        if ($conversation->messages()->count() === 0) {
            Message::create([
                'conversation_id' => $conversation->id,
                'role' => 'user',
                'content' => 'Hi, I need condo photos this week.',
                'metadata' => null,
            ]);
            Message::create([
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => 'Great. I can help. Please share your contact details.',
                'metadata' => ['type' => 'follow_up', 'field' => 'contact'],
            ]);
        }

        LeadEvent::query()->firstOrCreate(
            [
                'lead_profile_id' => $lead->id,
                'event_type' => 'clean_demo_created',
            ],
            [
                'payload' => ['note' => 'Minimal demo lead event'],
                'created_by' => $admin->id,
            ]
        );

        FollowUp::query()->firstOrCreate(
            [
                'lead_profile_id' => $lead->id,
                'method' => 'call',
                'due_at' => now()->addDay(),
            ],
            [
                'owner_user_id' => $admin->id,
                'status' => 'pending',
                'result_notes' => 'Minimal demo follow-up.',
            ]
        );

        $quote = QuoteBuild::query()->firstOrCreate(
            ['quote_id' => 'Q-CLEAN-DEMO-0001'],
            [
                'user_id' => $clientUser->id,
                'conversation_id' => $conversation->id,
                'lead_profile_id' => $lead->id,
                'visitor_id' => 'clean-demo-visitor',
                'status' => 'new',
                'listing_type' => 'condo',
                'services' => ['photo', 'drone'],
                'options' => [
                    'package_code' => 'custom',
                    'contact_name' => 'Alex Demo',
                    'contact_email' => 'alex.demo.lead@example.com',
                    'contact_phone' => '+15147770001',
                ],
                'line_items' => [
                    ['label' => 'Condo base', 'amount' => 80],
                    ['label' => 'Photo', 'amount' => 180],
                    ['label' => 'Drone', 'amount' => 140],
                ],
                'estimated_total' => 400,
                'currency' => 'USD',
                'notes' => 'Clean demo quote.',
                'submitted_at' => now()->subHours(4),
            ]
        );

        QuoteEvent::query()->firstOrCreate(
            [
                'quote_build_id' => $quote->id,
                'event_type' => 'clean_demo_submitted',
            ],
            [
                'payload' => ['status' => 'new'],
                'created_by' => $admin->id,
            ]
        );

        $project = ClientProject::query()->firstOrCreate(
            [
                'client_id' => $client->id,
                'title' => 'Clean Demo Project',
            ],
            [
                'lead_profile_id' => $lead->id,
                'quote_build_id' => $quote->id,
                'created_by' => $admin->id,
                'service_type' => 'photo,drone',
                'property_address' => 'Montreal - Demo Location',
                'scheduled_at' => now()->addDay(),
                'due_at' => now()->addDays(3),
                'status' => 'accepted',
                'notes' => 'Clean demo project.',
            ]
        );

        ClientInvoice::query()->firstOrCreate(
            ['invoice_number' => 'INV-CLEAN-DEMO-0001'],
            [
                'client_id' => $client->id,
                'client_project_id' => $project->id,
                'created_by' => $admin->id,
                'amount' => 400.00,
                'currency' => 'USD',
                'status' => 'sent',
                'issued_at' => now()->toDateString(),
                'due_date' => now()->addDays(5)->toDateString(),
                'notes' => 'Clean demo invoice.',
            ]
        );

        ClientMessage::query()->firstOrCreate(
            [
                'client_id' => $client->id,
                'client_project_id' => $project->id,
                'message' => 'Welcome to your project workspace. This is a demo update.',
            ],
            [
                'sender_user_id' => $admin->id,
                'sender_role' => 'admin',
                'sent_at' => now()->subHour(),
            ]
        );

        ClientServiceRequest::query()->firstOrCreate(
            [
                'client_id' => $client->id,
                'requested_service' => 'Photography',
            ],
            [
                'requester_user_id' => $clientUser->id,
                'subject' => 'Minimal demo request',
                'details' => 'Need photography for condo listing.',
                'preferred_date' => now()->addDays(2),
                'status' => 'new',
            ]
        );

        PanelNotification::query()->firstOrCreate(
            [
                'user_id' => $admin->id,
                'type' => 'new_quote_submission',
                'title' => 'Clean demo quote submitted',
            ],
            [
                'body' => 'Q-CLEAN-DEMO-0001 is ready for review.',
                'action_url' => '/admin/quotes',
                'data' => ['seeded' => 'clean_demo'],
            ]
        );

        PanelNotification::query()->firstOrCreate(
            [
                'user_id' => $clientUser->id,
                'type' => 'invoice_created',
                'title' => 'Your demo invoice is available',
            ],
            [
                'body' => 'INV-CLEAN-DEMO-0001 has been generated.',
                'action_url' => '/user/dashboard',
                'data' => ['seeded' => 'clean_demo'],
            ]
        );

        if (app()->runningInConsole()) {
            $this->command?->info('Clean demo data seeded successfully.');
            $this->command?->line('Admin: admin@maccento.com / admin@1234');
            $this->command?->line('Client: client.demo@maccento.com / demo@1234');
        }
    }
}
