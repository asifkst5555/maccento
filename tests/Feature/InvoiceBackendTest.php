<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ClientInvoice;
use App\Models\ClientProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceBackendTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_create_invoice_with_zero_amount(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('secret123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $client = Client::query()->create([
            'name' => 'Invoice Client',
            'email' => 'invoice-client@example.com',
            'status' => 'active',
        ]);

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.clients.index'))
            ->post(route('admin.clients.invoices.store', $client), [
                'amount' => '0',
                'currency' => 'USD',
                'status' => 'sent',
            ]);

        $response
            ->assertRedirect(route('admin.clients.index'))
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('client_invoices', 0);
    }

    public function test_admin_cannot_create_invoice_for_project_that_belongs_to_another_client(): void
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
            ->post(route('admin.clients.invoices.store', $client), [
                'client_project_id' => $otherProject->id,
                'amount' => '250.00',
                'currency' => 'USD',
                'status' => 'sent',
            ]);

        $response
            ->assertRedirect(route('admin.clients.show', $client))
            ->assertSessionHasErrors('client_project_id');

        $this->assertDatabaseCount('client_invoices', 0);
    }

    public function test_admin_can_create_invoice_for_valid_client_project(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('secret123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $client = Client::query()->create([
            'name' => 'Invoice Client',
            'email' => 'invoice-client@example.com',
            'status' => 'active',
        ]);

        $project = ClientProject::query()->create([
            'client_id' => $client->id,
            'title' => 'Main Project',
            'status' => 'accepted',
        ]);

        $response = $this
            ->actingAs($admin)
            ->from(route('admin.clients.show', $client))
            ->post(route('admin.clients.invoices.store', $client), [
                'client_project_id' => $project->id,
                'amount' => '250.00',
                'currency' => 'USD',
                'status' => 'sent',
            ]);

        $response
            ->assertRedirect(route('admin.clients.show', $client))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('client_invoices', 1);
        $invoice = ClientInvoice::query()->first();
        $this->assertNotNull($invoice);
        $this->assertSame($client->id, $invoice->client_id);
        $this->assertSame($project->id, $invoice->client_project_id);
    }
}
