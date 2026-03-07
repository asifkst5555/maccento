<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ClientMessage;
use App\Models\ClientProject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SendGridInboundParseWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_inbound_reply_in_client_timeline_and_maps_project_from_subject_tag(): void
    {
        putenv('SENDGRID_INBOUND_WEBHOOK_TOKEN=test-inbound-token');
        $_ENV['SENDGRID_INBOUND_WEBHOOK_TOKEN'] = 'test-inbound-token';
        $_SERVER['SENDGRID_INBOUND_WEBHOOK_TOKEN'] = 'test-inbound-token';

        $client = Client::query()->create([
            'name' => 'Alice Client',
            'email' => 'alice@example.com',
            'status' => 'active',
        ]);

        $project = ClientProject::query()->create([
            'client_id' => $client->id,
            'title' => 'Main Listing',
            'status' => 'accepted',
        ]);

        $response = $this->postJson('/api/webhooks/sendgrid/inbound?token=test-inbound-token', [
            'from' => 'Alice Client <alice@example.com>',
            'subject' => "Re: Project update [P#{$project->id}]",
            'text' => "Thanks for the update.\n\nOn Tue, team wrote:\nOld thread",
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('stored', true)
            ->assertJsonPath('client_id', $client->id)
            ->assertJsonPath('client_project_id', $project->id);

        $this->assertDatabaseHas('client_messages', [
            'client_id' => $client->id,
            'client_project_id' => $project->id,
            'sender_role' => 'client',
        ]);

        $stored = ClientMessage::query()->where('client_id', $client->id)->latest('id')->first();
        $this->assertNotNull($stored);
        $this->assertStringContainsString('Subject: Re: Project update', (string) $stored?->message);
        $this->assertStringContainsString('Thanks for the update.', (string) $stored?->message);
        $this->assertStringNotContainsString('Old thread', (string) $stored?->message);
    }

    public function test_it_maps_project_from_reply_headers_when_subject_has_no_tag(): void
    {
        putenv('SENDGRID_INBOUND_WEBHOOK_TOKEN=test-inbound-token');
        $_ENV['SENDGRID_INBOUND_WEBHOOK_TOKEN'] = 'test-inbound-token';
        $_SERVER['SENDGRID_INBOUND_WEBHOOK_TOKEN'] = 'test-inbound-token';

        $client = Client::query()->create([
            'name' => 'Bob Client',
            'email' => 'bob@example.com',
            'status' => 'active',
        ]);

        $project = ClientProject::query()->create([
            'client_id' => $client->id,
            'title' => 'Condo Shoot',
            'status' => 'accepted',
        ]);

        $response = $this->postJson('/api/webhooks/sendgrid/inbound?token=test-inbound-token', [
            'from' => 'bob@example.com',
            'subject' => 'Re: Thanks',
            'headers' => "In-Reply-To: <crm-p{$project->id}-log99@maccento.mail>\nReferences: <crm-p{$project->id}-log99@maccento.mail>",
            'text' => 'Looks good, thank you!',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('stored', true)
            ->assertJsonPath('client_id', $client->id)
            ->assertJsonPath('client_project_id', $project->id);

        $this->assertDatabaseHas('client_messages', [
            'client_id' => $client->id,
            'client_project_id' => $project->id,
            'sender_role' => 'client',
        ]);
    }

    public function test_it_returns_stored_false_when_sender_is_not_mapped_to_client(): void
    {
        putenv('SENDGRID_INBOUND_WEBHOOK_TOKEN=test-inbound-token');
        $_ENV['SENDGRID_INBOUND_WEBHOOK_TOKEN'] = 'test-inbound-token';
        $_SERVER['SENDGRID_INBOUND_WEBHOOK_TOKEN'] = 'test-inbound-token';

        $response = $this->postJson('/api/webhooks/sendgrid/inbound?token=test-inbound-token', [
            'from' => 'unknown@example.com',
            'subject' => 'Re: Hello',
            'text' => 'Test body',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('stored', false);

        $this->assertDatabaseCount('client_messages', 0);
    }
}
