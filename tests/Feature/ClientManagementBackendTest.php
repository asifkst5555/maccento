<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ClientMessage;
use App\Models\ClientProject;
use App\Models\ClientServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientManagementBackendTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_attach_message_to_project_that_belongs_to_another_client(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('secret123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $client = Client::query()->create([
            'name' => 'Primary Client',
            'email' => 'primary@example.com',
            'status' => 'active',
        ]);

        $otherClient = Client::query()->create([
            'name' => 'Other Client',
            'email' => 'other@example.com',
            'status' => 'active',
        ]);

        $otherProject = ClientProject::query()->create([
            'client_id' => $otherClient->id,
            'title' => 'Other Project',
            'status' => 'accepted',
        ]);

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.clients.show', $client))
            ->post(route('admin.clients.messages.store', $client), [
                'client_project_id' => $otherProject->id,
                'message' => 'Timeline update for wrong project.',
            ]);

        $response
            ->assertRedirect(route('admin.clients.show', $client))
            ->assertSessionHasErrors('client_project_id');

        $this->assertDatabaseCount('client_messages', 0);
    }

    public function test_admin_client_store_updates_existing_client_instead_of_creating_duplicate(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('secret123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $linkedUser = User::query()->create([
            'name' => 'Existing Client User',
            'email' => 'client@example.com',
            'password' => bcrypt('secret123'),
            'role' => 'client',
            'status' => 'active',
        ]);

        $client = Client::query()->create([
            'user_id' => $linkedUser->id,
            'created_by' => $admin->id,
            'name' => 'Old Name',
            'email' => 'client@example.com',
            'status' => 'inactive',
        ]);

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.clients.store'), [
                'name' => 'Updated Client Name',
                'email' => 'client@example.com',
                'password' => 'secret12345',
                'phone' => '1234567890',
                'company' => 'Updated Company',
                'role' => 'client',
                'status' => 'active',
                'notes' => 'Updated from admin client form.',
            ]);

        $client->refresh();

        $response->assertRedirect(route('admin.clients.show', $client));
        $this->assertDatabaseCount('clients', 1);
        $this->assertSame('Updated Client Name', $client->name);
        $this->assertSame('active', $client->status);
        $this->assertSame('Updated Company', $client->company);
        $this->assertSame($linkedUser->id, $client->user_id);
    }

    public function test_invalid_invoice_creation_does_not_partially_update_service_request_status(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('secret123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $client = Client::query()->create([
            'name' => 'Service Client',
            'email' => 'service-client@example.com',
            'status' => 'active',
        ]);

        $serviceRequest = ClientServiceRequest::query()->create([
            'client_id' => $client->id,
            'requested_service' => 'Twilight photos',
            'status' => 'new',
        ]);

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.clients.show', $client))
            ->post(route('admin.service-requests.status', $serviceRequest), [
                'status' => 'completed',
                'create_invoice' => '1',
                'invoice_amount' => '150.00',
            ]);

        $response
            ->assertRedirect(route('admin.clients.show', $client))
            ->assertSessionHasErrors('status');

        $serviceRequest->refresh();

        $this->assertSame('new', $serviceRequest->status);
        $this->assertDatabaseCount('client_invoices', 0);
        $this->assertDatabaseCount('client_messages', 0);
    }
}
