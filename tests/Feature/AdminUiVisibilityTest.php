<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ClientInvoice;
use App\Models\ClientProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUiVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_photographer_does_not_see_manager_only_admin_links_and_actions(): void
    {
        $photographer = User::query()->create([
            'name' => 'Photo User',
            'email' => 'photo@example.com',
            'password' => bcrypt('secret123'),
            'role' => 'photographer',
            'status' => 'active',
        ]);

        $client = Client::query()->create([
            'name' => 'Client One',
            'email' => 'client-one@example.com',
            'status' => 'active',
        ]);

        $project = ClientProject::query()->create([
            'client_id' => $client->id,
            'title' => 'Listing Shoot',
            'status' => 'accepted',
        ]);

        ClientInvoice::query()->create([
            'client_id' => $client->id,
            'client_project_id' => $project->id,
            'invoice_number' => 'INV-2001',
            'amount' => 250.00,
            'currency' => 'USD',
            'status' => 'sent',
            'issued_at' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
        ]);

        $this
            ->actingAs($photographer)
            ->get(route('admin.media-delivery.index'))
            ->assertOk()
            ->assertDontSee('Watermark Settings');

        $this
            ->actingAs($photographer)
            ->get(route('admin.clients.index'))
            ->assertForbidden();

        $this
            ->actingAs($photographer)
            ->get(route('admin.clients.show', $client))
            ->assertForbidden();

        $this
            ->actingAs($photographer)
            ->get(route('admin.projects.index'))
            ->assertOk()
            ->assertDontSee('Project Invoice')
            ->assertSee('Read only');
    }

    public function test_manager_sees_manager_level_admin_links_and_actions(): void
    {
        $manager = User::query()->create([
            'name' => 'Manager User',
            'email' => 'manager@example.com',
            'password' => bcrypt('secret123'),
            'role' => 'manager',
            'status' => 'active',
        ]);

        $client = Client::query()->create([
            'name' => 'Client One',
            'email' => 'client-one@example.com',
            'status' => 'active',
        ]);

        $project = ClientProject::query()->create([
            'client_id' => $client->id,
            'title' => 'Listing Shoot',
            'status' => 'accepted',
        ]);

        ClientInvoice::query()->create([
            'client_id' => $client->id,
            'client_project_id' => $project->id,
            'invoice_number' => 'INV-2002',
            'amount' => 250.00,
            'currency' => 'USD',
            'status' => 'sent',
            'issued_at' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
        ]);

        $this
            ->actingAs($manager)
            ->get(route('admin.media-delivery.index'))
            ->assertOk()
            ->assertSee('Watermark Settings');

        $this
            ->actingAs($manager)
            ->get(route('admin.clients.show', $client))
            ->assertOk()
            ->assertSee('Open Invoices')
            ->assertSee('Open project');

        $this
            ->actingAs($manager)
            ->get(route('admin.projects.index'))
            ->assertOk()
            ->assertSee('Project Invoice')
            ->assertSee('Save');
    }
}
