<?php

namespace Tests\Feature;

use App\Models\WebsiteFormSubmission;
use App\Services\LeadAutoCaptureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebsiteContactFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_website_contact_form_accepts_multiple_services(): void
    {
        $this->mock(LeadAutoCaptureService::class, function ($mock): void {
            $mock
                ->shouldReceive('captureAndWelcome')
                ->once()
                ->andReturn(null);
        });

        $response = $this->postJson('/api/website-form/submit', [
            'name' => 'Contact Lead',
            'email' => 'contact@example.com',
            'services' => ['Photography', 'Drone', 'Photography', '  Drone  ', 'Videography'],
            'region' => 'Montreal',
            'message' => 'Need media support for a new listing.',
            'source' => 'website_contact_form_submission',
            'page_url' => 'https://example.com/contact',
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'ok' => true,
            ]);

        $submission = WebsiteFormSubmission::query()->first();

        $this->assertNotNull($submission);
        $this->assertSame('Photography,Drone,Videography', $submission->service);
    }
}
