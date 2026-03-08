<?php

namespace App\Services;

class MaccentoKnowledgeService
{
    public function contextText(): string
    {
        return $this->websiteContextText();
    }

    public function websiteContextText(): string
    {
        $config = config('maccento_bot');
        $knowledge = config('maccento_knowledge.website', []);
        $packageConfig = config('package_builder');

        $company = data_get($config, 'company.name', 'Maccento');
        $location = data_get($config, 'company.location', 'Montreal');
        $email = (string) data_get($config, 'company.email', '');
        $phone = (string) data_get($config, 'company.phone', '');
        $services = data_get($config, 'services', []);
        $packages = data_get($config, 'packages', []);
        $faq = data_get($config, 'faq', []);
        $objections = data_get($config, 'objections', []);
        $turnaroundPhotos = data_get($config, 'turnaround.photos', '24-48 hours');
        $turnaroundVideo = data_get($config, 'turnaround.video', '72 hours');
        $positioning = data_get($config, 'company.positioning', '');
        $idealClients = data_get($config, 'company.ideal_clients', []);
        $brandPromises = data_get($config, 'company.brand_promises', []);
        $bookingGuidance = data_get($config, 'booking.guidance', []);
        $captureRequirements = data_get($config, 'booking.capture_requirements', []);
        $websiteTone = data_get($config, 'website_assistant.tone', '');
        $websiteRules = data_get($config, 'website_assistant.rules', []);
        $serviceAreaPrimary = (string) data_get($knowledge, 'service_area.primary', '');
        $serviceAreaSpecialCases = (string) data_get($knowledge, 'service_area.special_cases', '');
        $bookingFlow = data_get($knowledge, 'booking_flow', []);
        $pricingRules = data_get($knowledge, 'pricing_rules', []);
        $deliveryPolicy = data_get($knowledge, 'delivery_policy', []);
        $recommendations = data_get($knowledge, 'recommendations', []);
        $faqAnswers = data_get($knowledge, 'faq_answers', []);
        $policyClarity = data_get($knowledge, 'policy_clarity', []);
        $policyFallbackReply = (string) data_get($knowledge, 'policy_fallback_reply', '');
        $currency = (string) data_get($packageConfig, 'currency', 'USD');
        $packagePresets = data_get($packageConfig, 'package_presets', []);
        $packageServices = data_get($packageConfig, 'services', []);
        $listingBase = data_get($packageConfig, 'listing_base', []);
        $photoCount = data_get($packageConfig, 'photo_count', []);
        $videoType = data_get($packageConfig, 'video_type', []);
        $droneMode = data_get($packageConfig, 'drone_mode', []);
        $addOns = data_get($packageConfig, 'add_ons', []);

        $lines = [
            "Company: {$company}",
            "Location: {$location}",
            $email !== '' ? "Company email: {$email}" : null,
            $phone !== '' ? "Company phone: {$phone}" : null,
            $positioning !== '' ? "Positioning: {$positioning}" : null,
            'Services: ' . implode(', ', $services),
            "Turnaround: photos {$turnaroundPhotos}, video {$turnaroundVideo}",
            'Language behavior: Reply in EN or FR based on user language. Keep tone premium, consultative, and concise.',
            $websiteTone !== '' ? "Website assistant tone: {$websiteTone}" : null,
            'Packages (website cards):',
        ];

        if (is_array($idealClients) && $idealClients !== []) {
            $lines[] = 'Ideal clients: ' . implode(', ', $idealClients);
        }

        if ($serviceAreaPrimary !== '') {
            $lines[] = 'Primary service area: ' . $serviceAreaPrimary;
        }

        if ($serviceAreaSpecialCases !== '') {
            $lines[] = 'Service area special case rule: ' . $serviceAreaSpecialCases;
        }

        if (is_array($brandPromises) && $brandPromises !== []) {
            $lines[] = 'Brand promises: ' . implode(' | ', $brandPromises);
        }

        if (is_array($captureRequirements) && $captureRequirements !== []) {
            $lines[] = 'Required booking fields before submission: ' . implode(', ', $captureRequirements);
        }

        if (is_array($bookingGuidance) && $bookingGuidance !== []) {
            $lines[] = 'Booking guidance:';
            foreach ($bookingGuidance as $rule) {
                $lines[] = '- ' . (string) $rule;
            }
        }

        if (is_array($bookingFlow) && $bookingFlow !== []) {
            $lines[] = 'Website booking flow facts:';
            foreach ($bookingFlow as $fact) {
                $lines[] = '- ' . (string) $fact;
            }
        }

        foreach ($packages as $name => $package) {
            $lines[] = "- {$name}: " . ($package['price'] ?? 'Custom') . ' | ' . ($package['summary'] ?? '');
        }

        if (is_array($packagePresets) && $packagePresets !== []) {
            $lines[] = 'Package presets (fixed totals):';
            foreach ($packagePresets as $code => $preset) {
                $title = (string) ($preset['title'] ?? ucfirst((string) $code));
                $displayTotal = (string) ($preset['display_total'] ?? '');
                $servicesText = is_array($preset['services'] ?? null) ? implode(',', $preset['services']) : '';
                $lines[] = "- {$title} [{$code}] = {$displayTotal} {$currency}; services: {$servicesText}";
            }
        }

        $lines[] = 'Custom package pricing matrix:';
        $lines[] = 'Listing base: ' . json_encode($listingBase);
        $lines[] = 'Service price: ' . json_encode($packageServices);
        $lines[] = 'Photo count options: ' . json_encode($photoCount);
        $lines[] = 'Video type options: ' . json_encode($videoType);
        $lines[] = 'Drone mode options: ' . json_encode($droneMode);
        $lines[] = 'Add-ons: ' . json_encode($addOns);

        if (is_array($pricingRules) && $pricingRules !== []) {
            $lines[] = 'Pricing rules:';
            foreach ($pricingRules as $rule) {
                $lines[] = '- ' . (string) $rule;
            }
        }

        if (is_array($deliveryPolicy) && $deliveryPolicy !== []) {
            $lines[] = 'Delivery policy facts:';
            foreach ($deliveryPolicy as $fact) {
                $lines[] = '- ' . (string) $fact;
            }
        }

        if (is_array($recommendations) && $recommendations !== []) {
            $lines[] = 'Recommendation hints by scenario:';
            foreach ($recommendations as $scenario => $hint) {
                $lines[] = '- ' . (string) $scenario . ': ' . (string) $hint;
            }
        }

        if (is_array($faq) && $faq !== []) {
            $lines[] = 'FAQ snippets:';
            foreach ($faq as $item) {
                $q = (string) data_get($item, 'q', '');
                $a = (string) data_get($item, 'a', '');
                if ($q !== '' && $a !== '') {
                    $lines[] = "- Q: {$q} | A: {$a}";
                }
            }
        }

        if (is_array($objections) && $objections !== []) {
            $lines[] = 'Objection handling snippets:';
            foreach ($objections as $item) {
                $topic = (string) data_get($item, 'topic', '');
                $script = (string) data_get($item, 'script', '');
                if ($topic !== '' && $script !== '') {
                    $lines[] = "- {$topic}: {$script}";
                }
            }
        }

        if (is_array($websiteRules) && $websiteRules !== []) {
            $lines[] = 'Website assistant operating rules:';
            foreach ($websiteRules as $rule) {
                $lines[] = '- ' . (string) $rule;
            }
        }

        if (is_array($faqAnswers) && $faqAnswers !== []) {
            $lines[] = 'Website quick-answer facts:';
            foreach ($faqAnswers as $topic => $answer) {
                $lines[] = '- ' . (string) $topic . ': ' . (string) $answer;
            }
        }

        if (is_array($policyClarity) && $policyClarity !== []) {
            $lines[] = 'Policy clarity limits:';
            foreach ($policyClarity as $rule) {
                $lines[] = '- ' . (string) $rule;
            }
        }

        if ($policyFallbackReply !== '') {
            $lines[] = 'Policy fallback reply: ' . $this->injectContactDetails($policyFallbackReply, $email, $phone);
        }

        return implode("\n", array_filter($lines, static fn ($line): bool => $line !== null && $line !== ''));
    }

    public function adminContextText(): string
    {
        $config = config('maccento_bot');
        $knowledge = config('maccento_knowledge.admin', []);
        $company = data_get($config, 'company.name', 'Maccento');
        $location = data_get($config, 'company.location', 'Montreal');
        $email = (string) data_get($config, 'company.email', '');
        $phone = (string) data_get($config, 'company.phone', '');
        $positioning = data_get($config, 'company.positioning', '');
        $brandPromises = data_get($config, 'company.brand_promises', []);
        $modules = data_get($config, 'admin_assistant.modules', []);
        $roleRules = data_get($config, 'admin_assistant.role_rules', []);
        $rules = data_get($config, 'admin_assistant.rules', []);
        $tone = data_get($config, 'admin_assistant.tone', '');
        $systemSummary = data_get($knowledge, 'system_summary', []);
        $workflowFacts = data_get($knowledge, 'workflow_facts', []);
        $paymentAndDeliveryRules = data_get($knowledge, 'payment_and_delivery_rules', []);
        $emailCenterRules = data_get($knowledge, 'email_center_rules', []);
        $clientManagementRules = data_get($knowledge, 'client_management_rules', []);
        $quoteAndRevisionRules = data_get($knowledge, 'quote_and_revision_rules', []);
        $policyGaps = data_get($knowledge, 'policy_gaps', []);
        $policyFallbackReply = (string) data_get($knowledge, 'policy_fallback_reply', '');
        $roleBoundaries = data_get($knowledge, 'role_boundaries', []);

        $lines = [
            "Company: {$company}",
            "Location: {$location}",
            $email !== '' ? "Company email: {$email}" : null,
            $phone !== '' ? "Company phone: {$phone}" : null,
            $positioning !== '' ? "Positioning: {$positioning}" : null,
            $tone !== '' ? "Admin assistant tone: {$tone}" : null,
        ];

        if (is_array($brandPromises) && $brandPromises !== []) {
            $lines[] = 'Company promises: ' . implode(' | ', $brandPromises);
        }

        if (is_array($systemSummary) && $systemSummary !== []) {
            $lines[] = 'System summary:';
            foreach ($systemSummary as $fact) {
                $lines[] = '- ' . (string) $fact;
            }
        }

        if (is_array($modules) && $modules !== []) {
            $lines[] = 'CRM modules:';
            foreach ($modules as $module => $description) {
                $lines[] = '- ' . (string) $module . ': ' . (string) $description;
            }
        }

        if (is_array($roleRules) && $roleRules !== []) {
            $lines[] = 'Internal role boundaries:';
            foreach ($roleRules as $role => $description) {
                $lines[] = '- ' . strtoupper((string) $role) . ': ' . (string) $description;
            }
        }

        if (is_array($workflowFacts) && $workflowFacts !== []) {
            $lines[] = 'Workflow facts:';
            foreach ($workflowFacts as $fact) {
                $lines[] = '- ' . (string) $fact;
            }
        }

        if (is_array($paymentAndDeliveryRules) && $paymentAndDeliveryRules !== []) {
            $lines[] = 'Payment and delivery rules:';
            foreach ($paymentAndDeliveryRules as $rule) {
                $lines[] = '- ' . (string) $rule;
            }
        }

        if (is_array($emailCenterRules) && $emailCenterRules !== []) {
            $lines[] = 'Email Center rules:';
            foreach ($emailCenterRules as $rule) {
                $lines[] = '- ' . (string) $rule;
            }
        }

        if (is_array($clientManagementRules) && $clientManagementRules !== []) {
            $lines[] = 'Client management rules:';
            foreach ($clientManagementRules as $rule) {
                $lines[] = '- ' . (string) $rule;
            }
        }

        if (is_array($quoteAndRevisionRules) && $quoteAndRevisionRules !== []) {
            $lines[] = 'Quote and revision rules:';
            foreach ($quoteAndRevisionRules as $rule) {
                $lines[] = '- ' . (string) $rule;
            }
        }

        if (is_array($policyGaps) && $policyGaps !== []) {
            $lines[] = 'Policy gaps and escalation notes:';
            foreach ($policyGaps as $rule) {
                $lines[] = '- ' . (string) $rule;
            }
        }

        if ($policyFallbackReply !== '') {
            $lines[] = 'Policy fallback reply: ' . $this->injectContactDetails($policyFallbackReply, $email, $phone);
        }

        if (is_array($roleBoundaries) && $roleBoundaries !== []) {
            $lines[] = 'Role boundary reminders:';
            foreach ($roleBoundaries as $rule) {
                $lines[] = '- ' . (string) $rule;
            }
        }

        if (is_array($rules) && $rules !== []) {
            $lines[] = 'Admin assistant operating rules:';
            foreach ($rules as $rule) {
                $lines[] = '- ' . (string) $rule;
            }
        }

        return implode("\n", array_filter($lines, static fn ($line): bool => $line !== null && $line !== ''));
    }

    private function injectContactDetails(string $message, string $email, string $phone): string
    {
        $parts = [];
        if ($email !== '') {
            $parts[] = 'email at ' . $email;
        }
        if ($phone !== '') {
            $parts[] = 'phone at ' . $phone;
        }

        if ($parts === []) {
            return $message;
        }

        return str_replace('by email or phone', 'by ' . implode(' or ', $parts), $message);
    }
}
