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

        ClientProjectAssignment::query()->create([
            'client_project_id' => $project->id,
            'user_id' => $photographer->id,
            'assigned_by' => $photographer->id,
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


    public function test_assigned_photographer_can_upload_gallery_media_into_role_scoped_project_folder(): void
    {
        Storage::fake('public');

        $photographer = User::query()->create([
            'name' => 'Photo User',
            'email' => 'photo-upload@example.com',
            'password' => bcrypt('secret123'),
            'role' => 'photographer',
            'status' => 'active',
        ]);

        $client = Client::query()->create([
            'name' => 'Client Two',
            'email' => 'client-two@example.com',
            'status' => 'active',
        ]);

        $project = ClientProject::query()->create([
            'client_id' => $client->id,
            'title' => 'Luxury Listing Shoot',
            'status' => 'accepted',
        ]);

        ClientProjectAssignment::query()->create([
            'client_project_id' => $project->id,
            'user_id' => $photographer->id,
            'assigned_by' => $photographer->id,
        ]);

        $response = $this
            ->actingAs($photographer)
            ->post(route('admin.projects.media.store', $project), [
                'media_stage' => 'raw',
                'media_files' => [UploadedFile::fake()->image('front-elevation.jpg')],
            ]);

        $response->assertRedirect();

        $media = ClientProjectMedia::query()->firstOrFail();
        $this->assertSame($photographer->id, (int) $media->uploaded_by);
        $this->assertSame('raw', (string) $media->delivery_stage);
        $this->assertStringContainsString('/raw-footage/photographer/user-' . $photographer->id . '-photo-user/', (string) $media->path);
    }

    public function test_assigned_editor_can_upload_edited_media_into_role_scoped_project_folder(): void
    {
        Storage::fake('public');

        $editor = User::query()->create([
            'name' => 'Edit User',
            'email' => 'editor-upload@example.com',
            'password' => bcrypt('secret123'),
            'role' => 'editor',
            'status' => 'active',
        ]);

        $client = Client::query()->create([
            'name' => 'Client Three',
            'email' => 'client-three@example.com',
            'status' => 'active',
        ]);

        $project = ClientProject::query()->create([
            'client_id' => $client->id,
            'title' => 'Edited Media Project',
            'status' => 'editing',
        ]);

        ClientProjectAssignment::query()->create([
            'client_project_id' => $project->id,
            'user_id' => $editor->id,
            'assigned_by' => $editor->id,
        ]);

        $response = $this
            ->actingAs($editor)
            ->post(route('admin.projects.media.store', $project), [
                'media_stage' => 'edited',
                'media_files' => [UploadedFile::fake()->image('edited-front.jpg')],
            ]);

        $response->assertRedirect();

        $media = ClientProjectMedia::query()->firstOrFail();
        $this->assertSame($editor->id, (int) $media->uploaded_by);
        $this->assertSame('edited', (string) $media->delivery_stage);
        $this->assertStringContainsString('/edited-final/editor/user-' . $editor->id . '-edit-user/', (string) $media->path);
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
