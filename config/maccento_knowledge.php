<?php

return [
    'website' => [
        'service_area' => [
            'primary' => 'Greater Montreal',
            'special_cases' => 'Special locations can be discussed case by case.',
        ],
        'booking_flow' => [
            'Visitors can start from the AI assistant, package builder, or contact form.',
            'All intake paths feed the CRM so the team can review and follow up.',
            'Best practice is to collect name, email, services, location, and timeline before submission.',
        ],
        'pricing_rules' => [
            'Preset packages have fixed totals and should be described confidently.',
            'Custom package estimates depend on listing type, selected services, quantities, and add-ons.',
            'Do not promise an exact custom total unless it is produced from the pricing matrix.',
        ],
        'delivery_policy' => [
            'Photos are typically delivered in 24-48 hours.',
            'Video is typically delivered in around 72 hours.',
            'Drone capture depends on weather and safe flight conditions.',
            'Fast turnaround is a brand promise, but timing still depends on scope and conditions.',
            'Essential and Signature preset copy highlight 24-hour delivery in their marketing description.',
            'Priority Editing exists as a paid add-on in the custom package builder.',
        ],
        'recommendations' => [
            'condo' => 'Essential usually fits standard condo listings that mainly need polished HDR photography.',
            'home' => 'Signature is a strong fit when the listing benefits from both stills and motion coverage.',
            'premium' => 'Prestige is best when the marketing strategy needs photo, drone, cinematic video, social cut, and floor plan support.',
        ],
        'faq_answers' => [
            'package_builder' => 'The package builder supports both preset packages and custom service combinations.',
            'multiple_services' => 'Clients can combine photo, video, drone, floor plan, social media, and add-ons in one request.',
            'quote_expectation' => 'If exact pricing is not fixed, the system should explain that the quote depends on selected options and listing details.',
        ],
        'policy_clarity' => [
            'There is no explicit cancellation or refund policy configured in the current app knowledge.',
            'There is no explicit rush-fee rule beyond the Priority Editing add-on in the package builder.',
            'If a visitor asks about an unconfigured policy, the assistant should respond professionally and direct them to contact the team by email or phone for confirmation.',
        ],
        'policy_fallback_reply' => 'For cancellation, refund, or custom payment-term questions, please contact our team directly by email or phone and we will confirm the best option for your project.',
    ],
    'admin' => [
        'system_summary' => [
            'This CRM combines lead capture, quote pipeline, invoicing, project tracking, media delivery, notifications, and client portal workflows.',
            'Lead sources include website AI chat, package builder, and website contact forms.',
            'Once deals progress, work moves into projects, invoices, and client communication timelines.',
        ],
        'workflow_facts' => [
            'Lead statuses include new, qualified, contacted, won, lost, and nurturing.',
            'Quote statuses include new, reviewed, contacted, booked, and lost.',
            'Project statuses include accepted, shooting, editing, and complete.',
            'Invoice statuses include draft, sent, partial, paid, and overdue.',
            'Client service request statuses include new, accepted, in_progress, completed, and closed.',
        ],
        'payment_and_delivery_rules' => [
            'Client project downloads are locked until the project has a paid invoice.',
            'Media delivery workspace handles gallery uploads, final ZIP uploads, previews, and delivery readiness.',
            'Watermark rebuild operations apply to unpaid preview media and should not be described as affecting paid final delivery.',
            'Invoice PDF settings currently support tax inclusion and tax rate percentage only.',
            'Default invoice settings start with tax disabled and tax rate at 0 until changed by admin.',
        ],
        'email_center_rules' => [
            'Email Center includes inbox, sent, drafts, compose, automation settings, and AI-assisted drafting.',
            'Drafts can be autosaved, edited, sent, and deleted from the mailbox workspace.',
            'SendGrid-linked inbound replies can be mapped into client timelines and project threads.',
        ],
        'client_management_rules' => [
            'Client workspaces bring together projects, invoices, requests, and message timelines.',
            'Admins should use the client detail page to review a single project context or all projects for that client.',
            'Project-linked actions should stay aligned to the client that owns the project.',
        ],
        'quote_and_revision_rules' => [
            'Users can send quote revision requests from the client portal to the admin team.',
            'Fixed preset package quotes are locked for manual line-item editing.',
            'Custom quote line items can be updated and totals recalculated by admin.',
        ],
        'policy_gaps' => [
            'The CRM does not define a cancellation or refund policy in config or workflow code.',
            'The CRM does not define automatic late fees on overdue invoices.',
            'When staff ask about those items, the assistant should say they require business-policy confirmation and advise contacting the team by email or phone.',
        ],
        'policy_fallback_reply' => 'For cancellation, refund, or custom payment-term questions, advise the client to contact the team directly by email or phone so the correct policy can be confirmed professionally.',
        'role_boundaries' => [
            'Owner and admin can manage users and highest-level settings.',
            'Manager can operate most pipeline and delivery areas but not owner-only user management.',
            'Photographer and editor are delivery-focused roles and should not be guided into restricted pipeline write actions.',
        ],
    ],
];
