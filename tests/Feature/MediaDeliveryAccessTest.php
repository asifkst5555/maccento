<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ClientProject;
use App\Models\ClientProjectAssignment;
use App\Models\ClientProjectMedia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaDeliveryAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_photographer_can_view_project_media_from_admin_media_workspace(): void
    {
        Storage::fake('public');

        $user = User::query()->create([
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

        ClientProjectAssignment::query()->create([
            'client_project_id' => $project->id,
            'user_id' => $user->id,
            'assigned_by' => $user->id,
        ]);

        $file = UploadedFile::fake()->create('delivery.zip', 128, 'application/zip');
        $storedPath = $file->store('media/listing-shoot-' . $project->id . '/delivery', 'public');

        $media = ClientProjectMedia::query()->create([
            'client_project_id' => $project->id,
            'uploaded_by' => $user->id,
            'type' => 'final_zip',
            'disk' => 'public',
            'path' => $storedPath,
            'original_name' => 'delivery.zip',
            'mime_type' => 'application/zip',
            'size_bytes' => 131072,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('admin.projects.media.view', ['project' => $project, 'media' => $media]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/zip');
    }
}
