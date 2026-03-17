<?php

return [
    'company' => [
        'name' => 'Maccento Real Estate Media',
        'location' => 'Montreal, QC',
        'website_url' => 'https://maccento.ca',
        'email' => 'info@maccento.ca',
        'phone' => '(514) 951-9141',
        'positioning' => 'Premium real estate media production for brokers, agents, teams, and property marketers.',
        'ideal_clients' => [
            'Residential real estate brokers',
            'Real estate teams and agencies',
            'Developers and property marketers',
            'Listing coordinators who need fast turnaround and polished delivery',
        ],
        'brand_promises' => [
            'Fast and dependable turnaround',
            'Premium visual presentation for listings',
            'Clear communication from booking to delivery',
            'Flexible custom package building when one fixed package is not enough',
        ],
    ],

    'services' => [
        'Professional Photography (HDR)',
        'Cinematic Property Films',
        'Express Video Walkthroughs',
        'Drone Photography & Video',
        'Virtual Staging',
        'Day-to-Dusk Edits',
        '3D Tours (coming soon)',
        'Floor Plans (coming soon)',
        'Social Media Content for Brokers',
        'Photo Retouching & Media Enhancement',
    ],

    'packages' => [
        'Essential' => [
            'price' => '',
            'summary' => 'Ideal for condos and standard listings. Includes HDR photos and drone images with fast delivery.',
        ],
        'Signature' => [
            'price' => '',
            'summary' => 'For stronger marketing needs. Includes HDR photos and video teaser.',
        ],
        'Prestige' => [
            'price' => '',
            'summary' => 'Premium full media coverage: HDR, drone, cinematic walkthrough, social cut, and floor plan.',
        ],
        'Custom Build' => [
            'price' => 'Custom',
            'summary' => 'Pick only what you need across photo, video, drone, staging, and editing add-ons.',
        ],
    ],

    'turnaround' => [
        'photos' => '24-48 hours',
        'video' => 'Around 72 hours',
    ],

    'booking' => [
        'capture_requirements' => [
            'name',
            'email',
            'service_type',
            'location',
            'timeline',
        ],
        'contact_preferences' => [
            'email',
            'phone',
        ],
        'guidance' => [
            'Lead qualification should feel conversational, not like a form.',
            'Ask only one missing-field question at a time.',
            'Recommend one best-fit package or service direction when enough detail exists.',
            'Use a short confirmation summary before submission.',
        ],
    ],

    'faq' => [
        [
            'q' => 'How fast can you deliver?',
            'a' => 'Photos are usually delivered in 24-48h and video in around 72h depending on project scope.',
        ],
        [
            'q' => 'Do you serve only Montreal?',
            'a' => 'On-site capture is focused on Greater Montreal. We can discuss special locations case by case.',
        ],
        [
            'q' => 'Can I combine services in one request?',
            'a' => 'Yes. We can mix photo, drone, video, floor plan, and social media into one custom package.',
        ],
    ],

    'objections' => [
        [
            'topic' => 'price',
            'script' => 'I understand budget matters. We can keep only the essentials now and add options later, so you still launch fast without overspending.',
        ],
        [
            'topic' => 'speed',
            'script' => 'If timing is urgent, we can prioritize deliverables and align the package to your exact deadline.',
        ],
        [
            'topic' => 'not sure what to choose',
            'script' => 'No problem. I can recommend a package based on your listing type, marketing goal, and timeline in a few quick questions.',
        ],
        [
            'topic' => 'already using another provider',
            'script' => 'Understood. Many clients start with one trial listing with us to compare quality and turnaround before switching fully.',
        ],
    ],

    'website_assistant' => [
        'tone' => 'Premium, concise, consultative, and conversion-focused without sounding pushy.',
        'rules' => [
            'Answer only about Maccento services, packages, booking, delivery expectations, and listing-media recommendations.',
            'If asked an unrelated question, answer briefly and steer back to services or booking support.',
            'Do not invent pricing outside configured package or pricing-matrix context.',
            'If exact pricing is unavailable, say the quote depends on the selected services and listing details.',
            'When enough context exists, recommend the next best step clearly.',
        ],
    ],

    'admin_assistant' => [
        'tone' => 'Direct, practical, operations-focused, and professional.',
        'modules' => [
            'Dashboard' => 'High-level CRM overview for leads, projects, invoices, client activity, and notifications.',
            'Leads' => 'Review lead details, AI-assisted leads, package leads, status updates, follow-ups, and outreach history.',
            'Quotes' => 'Build manual quotes, review quote requests, adjust line items, resend quote emails, and track quote status.',
            'Invoices' => 'Create invoices from clients/projects, update payment status, review invoice settings, and download PDFs.',
            'Email Center' => 'Inbox, sent mail, drafts, automation settings, AI email writing, and message history.',
            'Projects' => 'Track project status, open client/project context, and move work from accepted through delivery stages.',
            'Media Delivery' => 'Upload gallery files, upload final ZIPs, preview media, and manage delivery readiness.',
            'Clients' => 'Create clients, open client workspaces, review requests, projects, invoices, and message timelines.',
            'Users' => 'Owner/admin only area for internal user account management.',
            'Watermark Settings' => 'Admin/owner/manager area for media watermark configuration and rebuild actions.',
        ],
        'role_rules' => [
            'owner' => 'Full CRM access including users and high-level settings.',
            'admin' => 'Full daily CRM operations including leads, invoices, email center, and delivery.',
            'manager' => 'Pipeline and delivery operations without owner-only user management.',
            'photographer' => 'Operational access to projects, media delivery, clients, and project media preview, but not pipeline write areas like leads/invoices/email center.',
            'editor' => 'Operational access similar to photographer for delivery-focused tasks.',
        ],
        'rules' => [
            'Answer based on CRM workflow and visible module structure.',
            'Do not claim to have changed records or performed actions.',
            'If a question needs live record inspection, say exactly where the admin should check inside the CRM.',
            'Prefer short operational steps over long explanations.',
            'Respect role boundaries when describing where a user can navigate.',
        ],
    ],
];
