<?php

namespace Tests\Unit;

use App\Services\MaccentoKnowledgeService;
use Tests\TestCase;

class MaccentoKnowledgeServiceTest extends TestCase
{
    public function test_it_builds_distinct_website_and_admin_contexts(): void
    {
        $service = app(MaccentoKnowledgeService::class);

        $website = $service->websiteContextText();
        $admin = $service->adminContextText();

        $this->assertStringContainsString('Required booking fields before submission', $website);
        $this->assertStringContainsString('Website assistant operating rules', $website);
        $this->assertStringContainsString('Primary service area: Greater Montreal', $website);
        $this->assertStringContainsString('Pricing rules:', $website);
        $this->assertStringContainsString('Policy clarity limits:', $website);
        $this->assertStringContainsString('There is no explicit cancellation or refund policy configured in the current app knowledge.', $website);
        $this->assertStringContainsString('Policy fallback reply:', $website);
        $this->assertStringContainsString('info@maccento.ca', $website);
        $this->assertStringContainsString('(514) 951-9141', $website);
        $this->assertStringContainsString('CRM modules', $admin);
        $this->assertStringContainsString('Internal role boundaries', $admin);
        $this->assertStringContainsString('Payment and delivery rules:', $admin);
        $this->assertStringContainsString('Client project downloads are locked until the project has a paid invoice.', $admin);
        $this->assertStringContainsString('Quote and revision rules:', $admin);
        $this->assertStringContainsString('Policy gaps and escalation notes:', $admin);
        $this->assertStringContainsString('contact the team directly by email at info@maccento.ca or phone at (514) 951-9141', $admin);
        $this->assertNotSame($website, $admin);
    }
}
