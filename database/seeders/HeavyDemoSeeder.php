<?php

namespace Database\Seeders;

use App\Models\AiUsageLog;
use App\Models\Client;
use App\Models\ClientInvoice;
use App\Models\ClientProject;
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

class HeavyDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Keep medium profile intact, then extend with higher-volume records.
        $this->call([DemoDataSeeder::class]);

        $admin = User::query()->where('email', 'admin@maccento.com')->first();
        $manager = User::query()->where('email', 'manager.demo@maccento.com')->first() ?? $admin;
        $photographer = User::query()->where('email', 'photographer.demo@maccento.com')->first() ?? $admin;

        if (!$admin) {
            return;
        }

        $clients = Client::query()->whereNotNull('user_id')->get();
        if ($clients->isEmpty()) {
            return;
        }

        $servicesPool = ['photo', 'photo,drone', 'photo,video', 'video,drone', 'photo,video,drone'];
        $propertyPool = ['condo', 'home', 'rental', 'chalet', 'other'];
        $locationPool = ['Montreal', 'Laval', 'Longueuil', 'Brossard', 'West Island', 'Boucherville'];
        $leadStatuses = ['new', 'qualified', 'contacted', 'won', 'lost', 'nurturing'];
        $quoteStatuses = ['new', 'reviewed', 'contacted', 'booked', 'lost'];
        $projectStatuses = ['accepted', 'shooting', 'editing', 'complete'];
        $invoiceStatuses = ['draft', 'sent', 'partial', 'paid', 'overdue'];

        $heavyLeads = [];
        for ($i = 1; $i <= 24; $i++) {
            $channel = $i % 2 === 0 ? 'package_builder' : 'website_widget';
            $leadStatus = $leadStatuses[$i % count($leadStatuses)];
            $service = $servicesPool[$i % count($servicesPool)];

            $conversation = Conversation::query()->firstOrCreate(
                ['visitor_id' => sprintf('heavy-demo-%03d', $i)],
                [
                    'channel' => $channel,
                    'status' => $i % 4 === 0 ? 'closed' : 'active',
                    'started_at' => now()->subDays(30 - $i),
                    'last_message_at' => now()->subDays(29 - $i),
                    'metadata' => [
                        'language' => $i % 3 === 0 ? 'fr' : 'en',
                        'seeded' => 'heavy',
                    ],
                ]
            );

            $lead = LeadProfile::query()->firstOrCreate(
                ['conversation_id' => $conversation->id],
                [
                    'name' => sprintf('Heavy Lead %02d', $i),
                    'email' => sprintf('lead.heavy%02d.demo@example.com', $i),
                    'phone' => sprintf('+1514999%04d', $i),
                    'service_type' => $service,
                    'property_type' => $propertyPool[$i % count($propertyPool)],
                    'location' => $locationPool[$i % count($locationPool)],
                    'timeline' => $i % 2 === 0 ? 'this week' : 'next week',
                    'score' => 45 + ($i % 11) * 4,
                    'status' => $leadStatus,
                    'qualified_at' => in_array($leadStatus, ['qualified', 'contacted', 'won'], true) ? now()->subDays($i % 10) : null,
                    'notes' => 'Heavy demo lead record.',
                ]
            );

            $heavyLeads[] = $lead;

            if ($conversation->messages()->count() === 0) {
                Message::create([
                    'conversation_id' => $conversation->id,
                    'role' => 'user',
                    'content' => "Need {$service} for listing in {$lead->location}.",
                    'metadata' => null,
                ]);
                Message::create([
                    'conversation_id' => $conversation->id,
                    'role' => 'assistant',
                    'content' => 'Thanks. I can build your package and booking request now.',
                    'metadata' => ['type' => 'follow_up', 'seeded' => 'heavy'],
                ]);
            }

            LeadEvent::query()->firstOrCreate(
                ['lead_profile_id' => $lead->id, 'event_type' => 'heavy_created'],
                ['payload' => ['channel' => $channel], 'created_by' => $admin->id]
            );

            LeadEvent::query()->firstOrCreate(
                ['lead_profile_id' => $lead->id, 'event_type' => 'heavy_scored'],
                ['payload' => ['score' => $lead->score], 'created_by' => $manager?->id ?? $admin->id]
            );

            FollowUp::query()->firstOrCreate(
                [
                    'lead_profile_id' => $lead->id,
                    'method' => $i % 2 === 0 ? 'call' : 'email',
                    'due_at' => now()->addDays(($i % 8) - 3),
                ],
                [
                    'owner_user_id' => $manager?->id ?? $admin->id,
                    'status' => $i % 5 === 0 ? 'completed' : 'pending',
                    'result_notes' => 'Heavy demo follow-up.',
                ]
            );

            AiUsageLog::query()->create([
                'conversation_id' => $conversation->id,
                'provider' => 'openrouter',
                'model' => 'arcee-ai/trinity-large-preview:free',
                'tokens_in' => 700 + ($i * 30),
                'tokens_out' => 350 + ($i * 18),
                'estimated_cost' => 0.000000,
                'duration_ms' => 900 + ($i * 20),
            ]);
        }

        $heavyProjects = [];
        for ($i = 1; $i <= 24; $i++) {
            $lead = $heavyLeads[($i - 1) % count($heavyLeads)];
            $client = $clients[($i - 1) % $clients->count()];
            $quoteStatus = $quoteStatuses[$i % count($quoteStatuses)];
            $amount = 260 + ($i * 25);

            $quote = QuoteBuild::query()->firstOrCreate(
                ['quote_id' => sprintf('Q-HEAVY-%04d', $i)],
                [
                    'user_id' => $client->user_id,
                    'conversation_id' => $lead->conversation_id,
                    'lead_profile_id' => $lead->id,
                    'visitor_id' => sprintf('heavy-quote-%03d', $i),
                    'status' => $quoteStatus,
                    'listing_type' => $propertyPool[$i % count($propertyPool)],
                    'services' => explode(',', $servicesPool[$i % count($servicesPool)]),
                    'options' => [
                        'package_code' => 'custom',
                        'contact_name' => $client->name,
                        'contact_email' => $client->email,
                        'contact_phone' => $client->phone,
                    ],
                    'line_items' => [
                        ['label' => 'Heavy package base', 'amount' => $amount - 90],
                        ['label' => 'Production add-ons', 'amount' => 90],
                    ],
                    'estimated_total' => $amount,
                    'currency' => 'USD',
                    'notes' => 'Heavy demo quote.',
                    'submitted_at' => now()->subDays(25 - $i),
                ]
            );

            QuoteEvent::query()->firstOrCreate(
                ['quote_build_id' => $quote->id, 'event_type' => 'heavy_created'],
                ['payload' => ['status' => 'new'], 'created_by' => $admin->id]
            );
            QuoteEvent::query()->firstOrCreate(
                ['quote_build_id' => $quote->id, 'event_type' => 'heavy_status'],
                ['payload' => ['status' => $quoteStatus], 'created_by' => $manager?->id ?? $admin->id]
            );

            $project = ClientProject::query()->firstOrCreate(
                [
                    'client_id' => $client->id,
                    'title' => sprintf('Heavy Project %02d', $i),
                ],
                [
                    'lead_profile_id' => $lead->id,
                    'quote_build_id' => $quote->id,
                    'created_by' => $admin->id,
                    'service_type' => $servicesPool[$i % count($servicesPool)],
                    'property_address' => sprintf('%d Heavy Ave, %s', 200 + $i, $locationPool[$i % count($locationPool)]),
                    'scheduled_at' => now()->subDays(2 - ($i % 5)),
                    'due_at' => now()->addDays(($i % 7) - 2),
                    'status' => $projectStatuses[$i % count($projectStatuses)],
                    'notes' => 'Heavy demo project record.',
                ]
            );

            $heavyProjects[] = $project;

            ClientInvoice::query()->firstOrCreate(
                ['invoice_number' => sprintf('INV-HEAVY-%04d', $i)],
                [
                    'client_id' => $client->id,
                    'client_project_id' => $project->id,
                    'created_by' => $admin->id,
                    'amount' => 280 + ($i * 35),
                    'currency' => 'USD',
                    'status' => $invoiceStatuses[$i % count($invoiceStatuses)],
                    'issued_at' => now()->subDays(24 - $i),
                    'due_date' => now()->addDays(($i % 9) - 4),
                    'paid_at' => $invoiceStatuses[$i % count($invoiceStatuses)] === 'paid' ? now()->subDay() : null,
                    'notes' => 'Heavy demo invoice.',
                ]
            );
        }

        // Add unread notifications volume for dropdown stress-testing.
        $targetUsers = collect([
            $admin->id,
            $manager?->id,
            $photographer?->id,
            ...$clients->pluck('user_id')->all(),
        ])->filter()->unique()->values();

        foreach ($targetUsers as $userId) {
            for ($i = 1; $i <= 8; $i++) {
                PanelNotification::query()->firstOrCreate(
                    [
                        'user_id' => $userId,
                        'type' => $i % 2 === 0 ? 'quote_status_updated' : 'invoice_created',
                        'title' => sprintf('Heavy Notification %02d', $i),
                    ],
                    [
                        'body' => 'Heavy demo notification payload for UI badge and filters.',
                        'action_url' => $i % 2 === 0 ? '/admin/quotes' : '/admin/invoices',
                        'data' => ['seeded' => 'heavy', 'seq' => $i],
                        'read_at' => $i % 4 === 0 ? now()->subHours($i) : null,
                    ]
                );
            }
        }

        if (app()->runningInConsole()) {
            $this->command?->info('Heavy demo data seeded successfully (20+ leads/quotes/invoices added).');
        }
    }
}

