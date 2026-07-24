<?php

namespace Tests\Feature;

use App\Events\CloudImportProgressEvent;
use App\Jobs\ProcessImportedFileJob;
use App\Models\Client;
use App\Models\ClientProject;
use App\Models\ClientProjectMedia;
use App\Models\CloudProviderSetting;
use App\Models\DropboxImportSession;
use App\Models\User;
use App\Services\StorageManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DropboxQueueImportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private ClientProject $project;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.dropbox.access_token' => 'fake-dropbox-token']);

        // Create Admin
        $this->admin = User::query()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        // Create Client & Project
        $client = Client::query()->create([
            'name' => 'Test Client',
            'email' => 'client@example.com',
            'status' => 'active',
        ]);

        $this->project = ClientProject::query()->create([
            'client_id' => $client->id,
            'title' => 'Test Project',
            'status' => 'accepted',
        ]);

        Storage::fake('public');
    }

    public function test_scan_preview_endpoint_returns_file_statistics_and_duplicates(): void
    {
        ClientProjectMedia::query()->create([
            'client_project_id' => $this->project->id,
            'uploaded_by' => $this->admin->id,
            'type' => 'image',
            'delivery_stage' => 'raw',
            'disk' => 'public',
            'path' => 'media/photo.jpg',
            'original_name' => 'photo.jpg',
            'size_bytes' => 1024,
            'dropbox_file_id' => 'id:file123',
        ]);

        Http::fake([
            'api.dropboxapi.com/2/sharing/list_shared_link_files' => Http::response([
                'entries' => [
                    [
                        '.tag' => 'file',
                        'name' => 'photo.jpg',
                        'id' => 'id:file123',
                        'size' => 1024,
                        'path_lower' => '/photo.jpg',
                    ],
                    [
                        '.tag' => 'file',
                        'name' => 'new-video.mp4',
                        'id' => 'id:video123',
                        'size' => 2048000,
                        'path_lower' => '/new-video.mp4',
                    ],
                ],
                'has_more' => false,
            ], 200),
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.projects.dropbox.scan-preview', $this->project), [
                'dropbox_url' => 'https://www.dropbox.com/sh/fake-folder-url',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'counts' => [
                    'images' => 1,
                    'videos' => 1,
                    'duplicates' => 1,
                ],
                'total_files' => 2,
            ]);
    }

    public function test_start_queue_import_dispatches_laravel_bus_batch(): void
    {
        Bus::fake();

        Http::fake([
            'api.dropboxapi.com/2/sharing/list_shared_link_files' => Http::response([
                'entries' => [
                    [
                        '.tag' => 'file',
                        'name' => 'photo.jpg',
                        'id' => 'id:file123',
                        'size' => 1024,
                        'path_lower' => '/photo.jpg',
                    ],
                ],
                'has_more' => false,
            ], 200),
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.projects.dropbox.start-queue-import', $this->project), [
                'dropbox_url' => 'https://www.dropbox.com/sh/fake-folder-url',
                'media_stage' => 'raw',
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('dropbox_import_sessions', [
            'client_project_id' => $this->project->id,
            'total_files' => 1,
        ]);

        Bus::assertBatched(function ($batch) {
            return $batch->jobs->count() === 1 &&
                   $batch->jobs->first() instanceof ProcessImportedFileJob;
        });
    }

    public function test_storage_quota_limit_blocks_import_start(): void
    {
        // Mock a massive folder list that exceeds limits
        Http::fake([
            'api.dropboxapi.com/2/sharing/list_shared_link_files' => Http::response([
                'entries' => [
                    [
                        '.tag' => 'file',
                        'name' => 'huge-video.mp4',
                        'id' => 'id:huge123',
                        'size' => StorageManagementService::DEFAULT_PROJECT_QUOTA + 1000,
                        'path_lower' => '/huge-video.mp4',
                    ],
                ],
                'has_more' => false,
            ], 200),
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.projects.dropbox.start-queue-import', $this->project), [
                'dropbox_url' => 'https://www.dropbox.com/sh/fake-folder-url',
                'media_stage' => 'raw',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('error', 'Storage Quota Exceeded. Project limit is 10 GB. Already used: 0 GB. Trying to import: 10240 MB.');
    }

    public function test_batchable_job_downloads_and_registers_file_and_broadcasts(): void
    {
        Event::fake();

        $imageContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
        Http::fake([
            'content.dropboxapi.com/2/sharing/get_shared_link_file' => Http::response($imageContent, 200),
        ]);

        $session = DropboxImportSession::create([
            'uuid' => 'fake-uuid-session',
            'client_project_id' => $this->project->id,
            'user_id' => $this->admin->id,
            'folder_url' => 'https://www.dropbox.com/sh/fake-folder-url',
            'status' => 'importing',
            'total_files' => 1,
            'processed_files' => 0,
            'imported_files' => 0,
            'duplicate_files' => 0,
            'failed_files' => 0,
            'total_size' => strlen($imageContent),
            'media_stage' => 'raw',
            'files_queue' => [],
        ]);

        $job = new ProcessImportedFileJob($session->id, [
            'id' => 'id:photo123',
            'name' => 'real-photo.jpg',
            'path' => '/Wedding/Bride/real-photo.jpg',
            'size' => strlen($imageContent),
            'type' => 'image',
        ]);

        $job->handle();

        $session->refresh();

        $this->assertEquals(1, $session->imported_files);
        $this->assertEquals(0, $session->failed_files);

        $this->assertDatabaseHas('client_project_media', [
            'client_project_id' => $this->project->id,
            'original_name' => 'real-photo.jpg',
            'folder_path' => 'Wedding/Bride',
        ]);

        $this->assertDatabaseHas('dropbox_import_file_logs', [
            'dropbox_import_session_id' => $session->id,
            'filename' => 'real-photo.jpg',
            'status' => 'completed',
        ]);

        Event::assertDispatched(CloudImportProgressEvent::class);
    }

    public function test_provider_connection_test_returns_health_status(): void
    {
        // Mock successful Dropbox response
        Http::fake([
            'api.dropboxapi.com/2/users/get_current_account' => Http::response([
                'name' => [
                    'display_name' => 'John Doe'
                ]
            ], 200),
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.media-delivery.providers.test'), [
                'provider' => 'dropbox',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Connection successful! Account: John Doe',
            ]);
    }

    public function test_cleanup_command_removes_expired_sessions_and_temp_files(): void
    {
        $session = DropboxImportSession::create([
            'uuid' => 'abandoned-uuid',
            'client_project_id' => $this->project->id,
            'user_id' => $this->admin->id,
            'folder_url' => 'https://www.dropbox.com/sh/fake-folder-url',
            'status' => 'importing',
            'total_files' => 10,
            'processed_files' => 0,
            'imported_files' => 0,
            'duplicate_files' => 0,
            'failed_files' => 0,
            'total_size' => 10000,
            'media_stage' => 'raw',
            'files_queue' => [],
        ]);

        \Illuminate\Support\Facades\DB::table('dropbox_import_sessions')
            ->where('id', $session->id)
            ->update([
                'created_at' => now()->subHours(30),
                'updated_at' => now()->subHours(30),
            ]);

        // Run Cleanup Command
        $this->artisan('cloud-import:cleanup')
            ->assertExitCode(0);

        $session->refresh();
        $this->assertEquals('failed', $session->status);
        $this->assertStringContainsString('Import session marked abandoned/stuck', $session->error_log[0]['error']);
    }
}
