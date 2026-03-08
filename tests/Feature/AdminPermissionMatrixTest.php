<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ClientProject;
use App\Models\ClientProjectMedia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminPermissionMatrixTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_role_matrix_matches_expected_route_access(): void
    {
        Storage::fake('public');

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

        $roles = [
            'owner',
            'admin',
            'manager',
            'photographer',
            'editor',
        ];

        $routes = [
            'admin.dashboard' => [
                'parameters' => [],
                'allowed' => $roles,
            ],
            'admin.projects.index' => [
                'parameters' => [],
                'allowed' => $roles,
            ],
            'admin.media-delivery.index' => [
                'parameters' => [],
                'allowed' => $roles,
            ],
            'admin.clients.index' => [
                'parameters' => [],
                'allowed' => $roles,
            ],
            'admin.clients.show' => [
                'parameters' => [$client],
                'allowed' => $roles,
            ],
            'admin.media-delivery.watermark.index' => [
                'parameters' => [],
                'allowed' => ['owner', 'admin', 'manager'],
            ],
            'admin.leads.index' => [
                'parameters' => [],
                'allowed' => ['owner', 'admin', 'manager'],
            ],
            'admin.invoices.index' => [
                'parameters' => [],
                'allowed' => ['owner', 'admin', 'manager'],
            ],
            'admin.emails.inbox' => [
                'parameters' => [],
                'allowed' => ['owner', 'admin', 'manager'],
            ],
            'admin.users.index' => [
                'parameters' => [],
                'allowed' => ['owner', 'admin'],
            ],
            'admin.projects.media.view' => [
                'parameters' => ['project' => $project, 'media' => $this->seedMediaForProject($project)],
                'allowed' => $roles,
            ],
        ];

        foreach ($roles as $role) {
            $user = User::query()->create([
                'name' => ucfirst($role) . ' User',
                'email' => $role . '@example.com',
                'password' => bcrypt('secret123'),
                'role' => $role,
                'status' => 'active',
            ]);

            foreach ($routes as $routeName => $config) {
                $response = $this
                    ->actingAs($user)
                    ->get(route($routeName, $config['parameters']));

                if (in_array($role, $config['allowed'], true)) {
                    $response->assertOk();
                    continue;
                }

                $response->assertForbidden();
            }
        }
    }

    private function seedMediaForProject(ClientProject $project): ClientProjectMedia
    {
        Storage::disk('public')->put('matrix/placeholder.zip', 'placeholder');

        return ClientProjectMedia::query()->create([
            'client_project_id' => $project->id,
            'type' => 'final_zip',
            'disk' => 'public',
            'path' => 'matrix/placeholder.zip',
            'original_name' => 'placeholder.zip',
            'mime_type' => 'application/zip',
            'size_bytes' => 1024,
        ]);
    }
}
