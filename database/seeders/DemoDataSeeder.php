<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\ClientInvoice;
use App\Models\ClientMessage;
use App\Models\ClientProject;
use App\Models\ClientServiceRequest;
use App\Models\AiUsageLog;
use App\Models\Conversation;
use App\Models\FollowUp;
use App\Models\LeadEvent;
use App\Models\LeadProfile;
use App\Models\Message;
use App\Models\PanelNotification;
use App\Models\QuoteBuild;
use App\Models\QuoteEvent;
use App\Models\User;
use App\Models\WebsiteFormSubmission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DemoDataSeeder extends Seeder
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

        $manager = User::query()->firstOrCreate(
            ['email' => 'manager.demo@maccento.com'],
            [
                'name' => 'Demo Manager',
                'phone' => '+15140000002',
                'role' => 'manager',
                'password' => 'demo@1234',
            ]
        );
        $manager->forceFill([
            'name' => 'Demo Manager',
            'phone' => '+15140000002',
            'role' => 'manager',
            'password' => 'demo@1234',
        ])->save();

        $photographer = User::query()->firstOrCreate(
            ['email' => 'photographer.demo@maccento.com'],
            [
                'name' => 'Demo Photographer',
                'phone' => '+15140000003',
                'role' => 'photographer',
                'password' => 'demo@1234',
            ]
        );
        $photographer->forceFill([
            'name' => 'Demo Photographer',
            'phone' => '+15140000003',
            'role' => 'photographer',
            'password' => 'demo@1234',
        ])->save();

        $editor = User::query()->firstOrCreate(
            ['email' => 'editor.demo@maccento.com'],
            [
                'name' => 'Demo Editor',
                'phone' => '+15140000004',
                'role' => 'editor',
                'password' => 'demo@1234',
            ]
        );
        $editor->forceFill([
            'name' => 'Demo Editor',
            'phone' => '+15140000004',
            'role' => 'editor',
            'password' => 'demo@1234',
        ])->save();

        $clientUsers = [
            [
                'name' => 'Sarah Johnson',
                'email' => 'client.sarah.demo@maccento.com',
                'phone' => '+15140001001',
                'role' => 'client',
            ],
            [
                'name' => 'Liam Tremblay',
                'email' => 'agent.liam.demo@maccento.com',
                'phone' => '+15140001002',
                'role' => 'agent',
            ],
            [
                'name' => 'Nadia Roy',
                'email' => 'client.nadia.demo@maccento.com',
                'phone' => '+15140001003',
                'role' => 'client',
            ],
        ];

        $clients = [];
        foreach ($clientUsers as $row) {
            $user = User::query()->firstOrCreate(
                ['email' => $row['email']],
                [
                    'name' => $row['name'],
                    'phone' => $row['phone'],
                    'role' => $row['role'],
                    'password' => 'demo@1234',
                ]
            );
            $user->forceFill([
                'name' => $row['name'],
                'phone' => $row['phone'],
                'role' => $row['role'],
                'password' => 'demo@1234',
            ])->save();

            $clients[] = Client::query()->firstOrCreate(
                ['email' => $row['email']],
                [
                    'user_id' => $user->id,
                    'created_by' => $admin->id,
                    'name' => $row['name'],
                    'phone' => $row['phone'],
                    'company' => 'Demo Realty Group',
                    'status' => 'active',
                    'notes' => 'Demo client record seeded for admin panel walkthrough.',
                ]
            );
        }

        $conversationSpecs = [
            [
                'visitor_id' => 'demo-chat-001',
                'channel' => 'website_widget',
                'lang' => 'en',
                'lead' => [
                    'name' => 'Mark Benson',
                    'email' => 'lead.mark.demo@example.com',
                    'phone' => '+15145551001',
                    'service_type' => 'photo,drone',
                    'property_type' => 'condo',
                    'location' => 'Downtown Montreal',
                    'timeline' => 'this week',
                    'status' => 'new',
                    'score' => 55,
                ],
                'messages' => [
                    ['role' => 'user', 'content' => 'Hi, I need condo photos and drone this week in Montreal.'],
                    ['role' => 'assistant', 'content' => 'Great, I can help with that. May I have your name and best contact?', 'metadata' => ['type' => 'follow_up', 'field' => 'name']],
                ],
            ],
            [
                'visitor_id' => 'demo-chat-002',
                'channel' => 'website_widget',
                'lang' => 'fr',
                'lead' => [
                    'name' => 'Julie Martin',
                    'email' => 'lead.julie.demo@example.com',
                    'phone' => '+15145551002',
                    'service_type' => 'video,photo',
                    'property_type' => 'home',
                    'location' => 'Laval',
                    'timeline' => 'next week',
                    'status' => 'qualified',
                    'score' => 78,
                ],
                'messages' => [
                    ['role' => 'user', 'content' => 'Bonjour, je veux photo et video pour une maison a Laval la semaine prochaine.'],
                    ['role' => 'assistant', 'content' => 'Parfait. Je peux preparer une proposition et vous aider a reserver rapidement.', 'metadata' => ['type' => 'confirmation_request']],
                ],
            ],
            [
                'visitor_id' => 'demo-pbx-001',
                'channel' => 'package_builder',
                'lang' => 'en',
                'lead' => [
                    'name' => 'Dylan Cooper',
                    'email' => 'lead.dylan.demo@example.com',
                    'phone' => '+15145551003',
                    'service_type' => 'photo,video,drone',
                    'property_type' => 'home',
                    'location' => 'West Island',
                    'timeline' => 'asap',
                    'status' => 'contacted',
                    'score' => 85,
                ],
                'messages' => [
                    ['role' => 'user', 'content' => 'Build package: home with photo + cinematic video + drone both.'],
                    ['role' => 'assistant', 'content' => 'Custom package draft ready. Estimated total: 690 USD.', 'metadata' => [
                        'type' => 'follow_up',
                        'journey' => 'package_builder_assist',
                        'package_draft' => [
                            'listing_type' => 'home',
                            'services' => ['photo', 'video', 'drone'],
                            'video_type' => 'cinematic',
                            'drone_mode' => 'both',
                        ],
                        'package_preview' => [
                            'currency' => 'USD',
                            'total' => 690,
                            'line_items' => [
                                ['label' => 'Home base', 'amount' => 120],
                                ['label' => 'Photo', 'amount' => 180],
                                ['label' => 'Video', 'amount' => 220],
                                ['label' => 'Drone', 'amount' => 140],
                                ['label' => 'Video type: cinematic', 'amount' => 120],
                                ['label' => 'Drone mode: both', 'amount' => 100],
                            ],
                        ],
                    ]],
                ],
            ],
        ];

        $leadProfiles = [];
        foreach ($conversationSpecs as $idx => $spec) {
            $conversation = Conversation::query()->firstOrCreate(
                ['visitor_id' => $spec['visitor_id']],
                [
                    'channel' => $spec['channel'],
                    'status' => 'active',
                    'started_at' => now()->subDays(5 - $idx),
                    'last_message_at' => now()->subDays(4 - $idx),
                    'metadata' => ['language' => $spec['lang'], 'seeded' => true],
                ]
            );

            $leadProfiles[] = LeadProfile::query()->firstOrCreate(
                ['conversation_id' => $conversation->id],
                [
                    ...$spec['lead'],
                    'qualified_at' => $spec['lead']['status'] === 'qualified' ? now()->subDays(2) : null,
                    'notes' => 'Seeded demo lead for CRM showcase.',
                ]
            );

            if ($conversation->messages()->count() === 0) {
                foreach ($spec['messages'] as $messageRow) {
                    Message::create([
                        'conversation_id' => $conversation->id,
                        'role' => $messageRow['role'],
                        'content' => $messageRow['content'],
                        'metadata' => $messageRow['metadata'] ?? null,
                    ]);
                }
            }
        }

        foreach ($leadProfiles as $i => $lead) {
            LeadEvent::query()->firstOrCreate(
                [
                    'lead_profile_id' => $lead->id,
                    'event_type' => 'seeded_demo_event_' . $i,
                ],
                [
                    'payload' => ['note' => 'Demo timeline event for admin preview'],
                    'created_by' => $admin->id,
                ]
            );
        }

        foreach ($leadProfiles as $lead) {
            FollowUp::query()->firstOrCreate(
                [
                    'lead_profile_id' => $lead->id,
                    'method' => 'call',
                    'due_at' => now()->addDays(1),
                ],
                [
                    'owner_user_id' => $manager->id,
                    'status' => 'pending',
                    'result_notes' => 'Demo follow-up scheduled from seeded data.',
                ]
            );
        }

        $quotes = [];
        $quoteSpecs = [
            ['package' => 'essential', 'status' => 'new', 'total' => 250, 'client' => $clients[0], 'lead' => $leadProfiles[0]],
            ['package' => 'signature', 'status' => 'reviewed', 'total' => 350, 'client' => $clients[1], 'lead' => $leadProfiles[1]],
            ['package' => 'prestige', 'status' => 'booked', 'total' => 500, 'client' => $clients[2], 'lead' => $leadProfiles[2]],
            ['package' => 'custom', 'status' => 'contacted', 'total' => 690, 'client' => $clients[0], 'lead' => $leadProfiles[2]],
            ['package' => 'custom', 'status' => 'lost', 'total' => 430, 'client' => $clients[1], 'lead' => $leadProfiles[0]],
        ];

        foreach ($quoteSpecs as $i => $spec) {
            $quoteId = sprintf('Q-DEMO-%04d', $i + 1);
            $quote = QuoteBuild::query()->firstOrCreate(
                ['quote_id' => $quoteId],
                [
                    'user_id' => $spec['client']->user_id,
                    'conversation_id' => $spec['lead']->conversation_id,
                    'lead_profile_id' => $spec['lead']->id,
                    'visitor_id' => 'demo-quote-' . ($i + 1),
                    'status' => $spec['status'],
                    'listing_type' => $spec['package'] === 'essential' ? 'condo' : 'home',
                    'services' => $spec['package'] === 'essential'
                        ? ['photo']
                        : ($spec['package'] === 'signature'
                            ? ['photo', 'video', 'drone']
                            : ['photo', 'video', 'drone', 'floor_plan']),
                    'options' => [
                        'package_code' => $spec['package'],
                        'contact_name' => $spec['client']->name,
                        'contact_email' => $spec['client']->email,
                        'contact_phone' => $spec['client']->phone,
                    ],
                    'line_items' => [
                        ['label' => ucfirst($spec['package']) . ' package', 'amount' => $spec['total']],
                    ],
                    'estimated_total' => $spec['total'],
                    'currency' => 'USD',
                    'notes' => 'Seeded quote demo record.',
                    'submitted_at' => now()->subDays(7 - $i),
                ]
            );
            $quotes[] = $quote;

            QuoteEvent::query()->firstOrCreate(
                [
                    'quote_build_id' => $quote->id,
                    'event_type' => 'seeded_status_event_' . $i,
                ],
                [
                    'payload' => ['status' => $spec['status']],
                    'created_by' => $admin->id,
                ]
            );
        }

        $projectSpecs = [
            ['title' => '123 Maple St - Condo Shoot', 'status' => 'accepted', 'client' => $clients[0], 'lead' => $leadProfiles[0], 'quote' => $quotes[0], 'service' => 'photo'],
            ['title' => '88 River Rd - Premium Listing', 'status' => 'shooting', 'client' => $clients[1], 'lead' => $leadProfiles[1], 'quote' => $quotes[1], 'service' => 'photo,video'],
            ['title' => '401 Pine Ave - Luxury Package', 'status' => 'editing', 'client' => $clients[2], 'lead' => $leadProfiles[2], 'quote' => $quotes[2], 'service' => 'photo,video,drone'],
            ['title' => '512 Harbor View - Complete', 'status' => 'complete', 'client' => $clients[0], 'lead' => $leadProfiles[2], 'quote' => $quotes[3], 'service' => 'photo,drone'],
        ];

        $projects = [];
        foreach ($projectSpecs as $i => $spec) {
            $project = ClientProject::query()->firstOrCreate(
                [
                    'client_id' => $spec['client']->id,
                    'title' => $spec['title'],
                ],
                [
                    'lead_profile_id' => $spec['lead']->id,
                    'quote_build_id' => $spec['quote']->id,
                    'created_by' => $admin->id,
                    'service_type' => $spec['service'],
                    'property_address' => 'Montreal Area - Demo Address #' . ($i + 1),
                    'scheduled_at' => Carbon::now()->subDays(2 - $i),
                    'due_at' => Carbon::now()->addDays($i - 1),
                    'status' => $spec['status'],
                    'notes' => 'Seeded project for pipeline/kanban demonstration.',
                ]
            );
            $projects[] = $project;
        }

        $invoiceSpecs = [
            ['project' => $projects[0], 'status' => 'sent', 'amount' => 250.00],
            ['project' => $projects[1], 'status' => 'partial', 'amount' => 350.00],
            ['project' => $projects[2], 'status' => 'paid', 'amount' => 500.00],
            ['project' => $projects[3], 'status' => 'overdue', 'amount' => 430.00],
        ];
        foreach ($invoiceSpecs as $i => $spec) {
            ClientInvoice::query()->firstOrCreate(
                ['invoice_number' => sprintf('INV-DEMO-%04d', $i + 1)],
                [
                    'client_id' => $spec['project']->client_id,
                    'client_project_id' => $spec['project']->id,
                    'created_by' => $admin->id,
                    'amount' => $spec['amount'],
                    'currency' => 'USD',
                    'status' => $spec['status'],
                    'issued_at' => now()->subDays(10 - $i),
                    'due_date' => now()->addDays($i - 2),
                    'paid_at' => $spec['status'] === 'paid' ? now()->subDay() : null,
                    'notes' => 'Seeded invoice record.',
                ]
            );
        }

        foreach ($projects as $project) {
            ClientMessage::query()->firstOrCreate(
                [
                    'client_id' => $project->client_id,
                    'client_project_id' => $project->id,
                    'message' => 'Demo update for project: ' . $project->title,
                ],
                [
                    'sender_user_id' => $admin->id,
                    'sender_role' => 'admin',
                    'sent_at' => now()->subHours(6),
                ]
            );
        }

        foreach ($clients as $i => $client) {
            ClientServiceRequest::query()->firstOrCreate(
                [
                    'client_id' => $client->id,
                    'requested_service' => 'Photography + Drone',
                ],
                [
                    'requester_user_id' => $client->user_id,
                    'subject' => 'Demo service request #' . ($i + 1),
                    'details' => 'Need a complete media package for an upcoming listing.',
                    'preferred_date' => now()->addDays($i + 2),
                    'status' => $i % 2 === 0 ? 'new' : 'in_progress',
                ]
            );
        }

        $submissionStatuses = ['new', 'reviewed', 'qualified', 'won', 'lost'];
        foreach ($submissionStatuses as $i => $status) {
            WebsiteFormSubmission::query()->firstOrCreate(
                [
                    'email' => "website.demo{$i}@example.com",
                ],
                [
                    'name' => 'Website Demo Lead ' . ($i + 1),
                    'company' => 'Demo Agency ' . ($i + 1),
                    'phone' => '+1514666000' . $i,
                    'service' => $i % 2 === 0 ? 'photography' : 'photo,video',
                    'region' => 'Montreal',
                    'message' => 'Demo form submission for CRM list view.',
                    'status' => $status,
                    'source' => 'website',
                    'page_url' => '/contact',
                    'ip_address' => '127.0.0.1',
                    'submitted_at' => now()->subDays($i),
                ]
            );
        }

        $notificationRows = [
            ['user_id' => $admin->id, 'type' => 'new_quote_submission', 'title' => 'New quote submitted', 'body' => 'Demo quote Q-DEMO-0001 has been submitted.', 'action' => '/admin/quotes'],
            ['user_id' => $admin->id, 'type' => 'invoice_created', 'title' => 'Invoice created', 'body' => 'INV-DEMO-0003 is ready.', 'action' => '/admin/invoices'],
            ['user_id' => $manager->id, 'type' => 'new_service_request', 'title' => 'Service request', 'body' => 'New client request received.', 'action' => '/admin/clients'],
            ['user_id' => $clients[0]->user_id, 'type' => 'quote_status_updated', 'title' => 'Quote updated', 'body' => 'Your quote is now reviewed.', 'action' => '/user/dashboard'],
            ['user_id' => $clients[1]->user_id, 'type' => 'invoice_status_updated', 'title' => 'Invoice update', 'body' => 'Your invoice status changed to paid.', 'action' => '/user/dashboard'],
        ];

        foreach ($notificationRows as $row) {
            PanelNotification::query()->firstOrCreate(
                [
                    'user_id' => $row['user_id'],
                    'type' => $row['type'],
                    'title' => $row['title'],
                ],
                [
                    'body' => $row['body'],
                    'action_url' => $row['action'],
                    'data' => ['seeded' => true],
                    'read_at' => null,
                ]
            );
        }

        // Extended seeded dataset for full dashboard demos.
        $extraLeadStatus = ['new', 'qualified', 'contacted', 'won', 'lost', 'nurturing'];
        $extraQuoteStatus = ['new', 'reviewed', 'contacted', 'booked', 'lost'];
        $extraProjectStatus = ['accepted', 'shooting', 'editing', 'complete'];
        $extraInvoiceStatus = ['draft', 'sent', 'partial', 'paid', 'overdue'];
        $extraRequestStatus = ['new', 'accepted', 'in_progress', 'completed', 'closed'];
        $extraServices = ['photo', 'photo,drone', 'photo,video', 'video,drone', 'photo,video,drone'];
        $extraProperty = ['condo', 'home', 'rental', 'chalet', 'other'];
        $extraLocations = ['Montreal', 'Laval', 'Longueuil', 'Brossard', 'West Island'];
        $extraNames = ['Olivia Carter', 'Noah Singh', 'Emma Wilson', 'Lucas Gagnon', 'Mia Lopez', 'Ethan Patel', 'Sofia Roy', 'Jacob Martin', 'Ava Chen', 'Liam Scott'];

        $extraLeads = [];
        for ($i = 0; $i < 10; $i++) {
            $channel = $i % 2 === 0 ? 'website_widget' : 'package_builder';
            $name = $extraNames[$i];
            $service = $extraServices[$i % count($extraServices)];
            $status = $extraLeadStatus[$i % count($extraLeadStatus)];
            $email = sprintf('lead.extra%02d.demo@example.com', $i + 1);

            $conversation = Conversation::query()->firstOrCreate(
                ['visitor_id' => sprintf('demo-extra-%03d', $i + 1)],
                [
                    'channel' => $channel,
                    'status' => $i % 3 === 0 ? 'closed' : 'active',
                    'started_at' => now()->subDays(14 - $i),
                    'last_message_at' => now()->subDays(13 - $i),
                    'metadata' => [
                        'language' => $i % 3 === 0 ? 'fr' : 'en',
                        'seeded' => true,
                        'source' => $channel === 'package_builder' ? 'package_popup' : 'ai_widget',
                    ],
                ]
            );

            $lead = LeadProfile::query()->firstOrCreate(
                ['conversation_id' => $conversation->id],
                [
                    'name' => $name,
                    'email' => $email,
                    'phone' => sprintf('+15145558%03d', $i + 10),
                    'service_type' => $service,
                    'property_type' => $extraProperty[$i % count($extraProperty)],
                    'location' => $extraLocations[$i % count($extraLocations)],
                    'timeline' => $i % 2 === 0 ? 'this week' : 'next week',
                    'score' => 40 + ($i * 5),
                    'status' => $status,
                    'qualified_at' => in_array($status, ['qualified', 'contacted', 'won'], true) ? now()->subDays(max(1, 9 - $i)) : null,
                    'notes' => 'Extended demo lead for filtering and segmentation.',
                ]
            );

            $extraLeads[] = $lead;

            if ($conversation->messages()->count() === 0) {
                Message::create([
                    'conversation_id' => $conversation->id,
                    'role' => 'user',
                    'content' => "Hello, I need {$service} for a listing in {$lead->location}.",
                    'metadata' => null,
                ]);
                Message::create([
                    'conversation_id' => $conversation->id,
                    'role' => 'assistant',
                    'content' => 'Great, I can help. Please share your contact details and desired timeline.',
                    'metadata' => [
                        'type' => 'follow_up',
                        'journey' => $channel === 'package_builder' ? 'package_builder_assist' : 'general_lead_capture',
                    ],
                ]);
            }

            LeadEvent::query()->firstOrCreate(
                [
                    'lead_profile_id' => $lead->id,
                    'event_type' => 'seeded_extended_created',
                ],
                [
                    'payload' => ['channel' => $channel, 'status' => $status],
                    'created_by' => $admin->id,
                ]
            );

            LeadEvent::query()->firstOrCreate(
                [
                    'lead_profile_id' => $lead->id,
                    'event_type' => 'seeded_extended_followup',
                ],
                [
                    'payload' => ['method' => $i % 2 === 0 ? 'call' : 'email'],
                    'created_by' => $manager->id,
                ]
            );

            FollowUp::query()->firstOrCreate(
                [
                    'lead_profile_id' => $lead->id,
                    'method' => $i % 2 === 0 ? 'call' : 'email',
                    'due_at' => now()->addDays(($i % 5) - 2),
                ],
                [
                    'owner_user_id' => $i % 2 === 0 ? $manager->id : $admin->id,
                    'status' => $i % 4 === 0 ? 'completed' : 'pending',
                    'result_notes' => 'Extended demo follow-up entry.',
                ]
            );

            AiUsageLog::query()->create([
                'conversation_id' => $conversation->id,
                'provider' => 'openrouter',
                'model' => 'arcee-ai/trinity-large-preview:free',
                'tokens_in' => 600 + ($i * 40),
                'tokens_out' => 320 + ($i * 20),
                'estimated_cost' => 0.0000,
                'duration_ms' => 700 + ($i * 35),
            ]);
        }

        $allLeads = array_merge($leadProfiles, $extraLeads);

        $extraQuotes = [];
        for ($i = 0; $i < 12; $i++) {
            $lead = $allLeads[$i % count($allLeads)];
            $client = $clients[$i % count($clients)];
            $status = $extraQuoteStatus[$i % count($extraQuoteStatus)];
            $services = explode(',', $extraServices[$i % count($extraServices)]);
            $quoteTotal = 240 + ($i * 35);
            $quoteId = sprintf('Q-DEMO-EXT-%04d', $i + 1);

            $quote = QuoteBuild::query()->firstOrCreate(
                ['quote_id' => $quoteId],
                [
                    'user_id' => $client->user_id,
                    'conversation_id' => $lead->conversation_id,
                    'lead_profile_id' => $lead->id,
                    'visitor_id' => sprintf('demo-quote-ext-%03d', $i + 1),
                    'status' => $status,
                    'listing_type' => $extraProperty[$i % count($extraProperty)],
                    'services' => $services,
                    'options' => [
                        'package_code' => 'custom',
                        'contact_name' => $client->name,
                        'contact_email' => $client->email,
                        'contact_phone' => $client->phone,
                    ],
                    'line_items' => [
                        ['label' => 'Base package', 'amount' => $quoteTotal - 80],
                        ['label' => 'Add-ons', 'amount' => 80],
                    ],
                    'estimated_total' => $quoteTotal,
                    'currency' => 'USD',
                    'notes' => 'Extended demo quote for analytics and status pipeline.',
                    'submitted_at' => now()->subDays(12 - $i),
                ]
            );

            $extraQuotes[] = $quote;

            QuoteEvent::query()->firstOrCreate(
                [
                    'quote_build_id' => $quote->id,
                    'event_type' => 'seeded_extended_quote_created',
                ],
                [
                    'payload' => ['status' => 'new'],
                    'created_by' => $admin->id,
                ]
            );

            QuoteEvent::query()->firstOrCreate(
                [
                    'quote_build_id' => $quote->id,
                    'event_type' => 'seeded_extended_quote_status',
                ],
                [
                    'payload' => ['status' => $status],
                    'created_by' => $manager->id,
                ]
            );
        }

        $allQuotes = array_merge($quotes, $extraQuotes);
        $extraProjects = [];
        for ($i = 0; $i < 10; $i++) {
            $status = $extraProjectStatus[$i % count($extraProjectStatus)];
            $client = $clients[$i % count($clients)];
            $lead = $allLeads[$i % count($allLeads)];
            $quote = $allQuotes[$i % count($allQuotes)];

            $project = ClientProject::query()->firstOrCreate(
                [
                    'client_id' => $client->id,
                    'title' => sprintf('Extended Demo Project #%02d', $i + 1),
                ],
                [
                    'lead_profile_id' => $lead->id,
                    'quote_build_id' => $quote->id,
                    'created_by' => $i % 2 === 0 ? $admin->id : $manager->id,
                    'service_type' => $extraServices[$i % count($extraServices)],
                    'property_address' => sprintf('%d Demo Street, %s', 100 + $i, $extraLocations[$i % count($extraLocations)]),
                    'scheduled_at' => now()->subDays(3 - ($i % 4)),
                    'due_at' => now()->addDays(($i % 6) - 1),
                    'status' => $status,
                    'notes' => $status === 'complete'
                        ? 'Completed demo project for history view.'
                        : 'Ongoing demo project for production pipeline view.',
                ]
            );

            $extraProjects[] = $project;
        }

        $allProjects = array_merge($projects, $extraProjects);
        for ($i = 0; $i < 12; $i++) {
            $project = $allProjects[$i % count($allProjects)];
            $invoiceStatus = $extraInvoiceStatus[$i % count($extraInvoiceStatus)];
            $amount = 260 + ($i * 45);

            ClientInvoice::query()->firstOrCreate(
                ['invoice_number' => sprintf('INV-DEMO-EXT-%04d', $i + 1)],
                [
                    'client_id' => $project->client_id,
                    'client_project_id' => $project->id,
                    'created_by' => $i % 2 === 0 ? $admin->id : $manager->id,
                    'amount' => $amount,
                    'currency' => 'USD',
                    'status' => $invoiceStatus,
                    'issued_at' => now()->subDays(18 - $i),
                    'due_date' => now()->addDays(($i % 8) - 3),
                    'paid_at' => $invoiceStatus === 'paid' ? now()->subDays(1) : null,
                    'notes' => 'Extended seeded invoice record for paid/unpaid dashboards.',
                ]
            );
        }

        foreach ($allProjects as $i => $project) {
            ClientMessage::query()->firstOrCreate(
                [
                    'client_id' => $project->client_id,
                    'client_project_id' => $project->id,
                    'message' => sprintf('Project update %02d: shoot/edit status synchronized.', $i + 1),
                ],
                [
                    'sender_user_id' => $i % 2 === 0 ? $admin->id : $photographer->id,
                    'sender_role' => $i % 2 === 0 ? 'admin' : 'photographer',
                    'sent_at' => now()->subHours(12 - ($i % 8)),
                ]
            );
        }

        for ($i = 0; $i < 10; $i++) {
            $client = $clients[$i % count($clients)];
            ClientServiceRequest::query()->firstOrCreate(
                [
                    'client_id' => $client->id,
                    'subject' => sprintf('Extended demo request #%02d', $i + 1),
                ],
                [
                    'requester_user_id' => $client->user_id,
                    'requested_service' => $extraServices[$i % count($extraServices)],
                    'details' => 'Extended demo service request for workflow and status testing.',
                    'preferred_date' => now()->addDays($i + 1),
                    'status' => $extraRequestStatus[$i % count($extraRequestStatus)],
                ]
            );
        }

        for ($i = 0; $i < 12; $i++) {
            $status = $submissionStatuses[$i % count($submissionStatuses)];
            WebsiteFormSubmission::query()->firstOrCreate(
                ['email' => sprintf('website.extra%02d@example.com', $i + 1)],
                [
                    'name' => sprintf('Website Extended Lead %02d', $i + 1),
                    'company' => sprintf('Extended Agency %02d', $i + 1),
                    'phone' => sprintf('+15147770%03d', $i + 1),
                    'service' => $extraServices[$i % count($extraServices)],
                    'region' => $extraLocations[$i % count($extraLocations)],
                    'message' => 'Extended website submission for admin table/filter demo.',
                    'status' => $status,
                    'source' => $i % 2 === 0 ? 'website' : 'landing_page',
                    'page_url' => $i % 2 === 0 ? '/contact' : '/our-plan',
                    'ip_address' => '127.0.0.1',
                    'submitted_at' => now()->subDays($i),
                ]
            );
        }

        $notificationUsers = [
            $admin->id,
            $manager->id,
            $photographer->id,
            $editor->id,
            ...array_map(static fn (Client $c): int => (int) $c->user_id, $clients),
        ];
        $notificationTypes = [
            ['type' => 'new_quote_submission', 'title' => 'New quote submitted', 'body' => 'A new quote request needs review.', 'url' => '/admin/quotes'],
            ['type' => 'quote_status_updated', 'title' => 'Quote status updated', 'body' => 'Quote was updated by the team.', 'url' => '/admin/quotes'],
            ['type' => 'invoice_created', 'title' => 'New invoice created', 'body' => 'A new invoice is now available.', 'url' => '/admin/invoices'],
            ['type' => 'invoice_status_updated', 'title' => 'Invoice status changed', 'body' => 'Invoice payment status has changed.', 'url' => '/admin/invoices'],
            ['type' => 'new_message', 'title' => 'New project message', 'body' => 'You have a new message in project timeline.', 'url' => '/admin/projects'],
            ['type' => 'lead_status_updated', 'title' => 'Lead status updated', 'body' => 'A lead progressed in the pipeline.', 'url' => '/admin/leads'],
        ];

        foreach ($notificationUsers as $userIndex => $userId) {
            foreach ($notificationTypes as $typeIndex => $notificationType) {
                PanelNotification::query()->firstOrCreate(
                    [
                        'user_id' => $userId,
                        'type' => $notificationType['type'],
                        'title' => $notificationType['title'] . ' #' . ($userIndex + 1) . '-' . ($typeIndex + 1),
                    ],
                    [
                        'body' => $notificationType['body'],
                        'action_url' => $notificationType['url'],
                        'data' => ['seeded' => true, 'segment' => 'extended'],
                        'read_at' => ($typeIndex % 3 === 0) ? now()->subHours($typeIndex + 1) : null,
                    ]
                );
            }
        }

        if (app()->runningInConsole()) {
            $this->command?->info('Demo data seeded successfully.');
            $this->command?->info('Demo login:');
            $this->command?->line('Admin: admin@maccento.com / admin@1234');
            $this->command?->line('Manager: manager.demo@maccento.com / demo@1234');
            $this->command?->line('Photographer: photographer.demo@maccento.com / demo@1234');
            $this->command?->line('Editor: editor.demo@maccento.com / demo@1234');
            $this->command?->line('Client: client.sarah.demo@maccento.com / demo@1234');
        }
    }
}
