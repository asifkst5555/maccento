<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ClientProject;
use App\Models\ClientProjectMedia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminRoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_photographer_can_access_media_workspace_but_not_watermark_settings(): void
    {
        Storage::fake('public');

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

        $file = UploadedFile::fake()->create('delivery.zip', 128, 'application/zip');
        $storedPath = $file->store('media/listing-shoot-' . $project->id . '/delivery', 'public');

        $media = ClientProjectMedia::query()->create([
            'client_project_id' => $project->id,
            'uploaded_by' => $photographer->id,
            'type' => 'final_zip',
            'disk' => 'public',
            'path' => $storedPath,
            'original_name' => 'delivery.zip',
            'mime_type' => 'application/zip',
            'size_bytes' => 131072,
        ]);

        $this
            ->actingAs($photographer)
            ->get(route('admin.media-delivery.index'))
            ->assertOk();

        $this
            ->actingAs($photographer)
            ->get(route('admin.projects.media.view', ['project' => $project, 'media' => $media]))
            ->assertOk();

        $this
            ->actingAs($photographer)
            ->get(route('admin.media-delivery.watermark.index'))
            ->assertForbidden();
    }

    public function test_manager_can_access_watermark_settings(): void
    {
        $manager = User::query()->create([
            'name' => 'Manager User',
            'email' => 'manager@example.com',
            'password' => bcrypt('secret123'),
            'role' => 'manager',
            'status' => 'active',
        ]);

        $this
            ->actingAs($manager)
            ->get(route('admin.media-delivery.watermark.index'))
            ->assertOk();
    }
}
