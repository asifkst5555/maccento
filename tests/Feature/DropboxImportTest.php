<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ClientProject;
use App\Models\ClientProjectMedia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DropboxImportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private ClientProject $project;

    protected function setUp(): void
    {
        parent::setUp();

        // Configure services config token
        config(['services.dropbox.access_token' => 'fake-dropbox-token']);

        // Create admin user
        $this->admin = User::query()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        // Create client and project
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

    public function test_scan_validates_dropbox_url(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.projects.dropbox.scan', $this->project), [
                'dropbox_url' => 'https://invalid-url.com',
            ]);

        $response->assertStatus(422);
    }

    public function test_scan_lists_and_filters_dropbox_files_with_counts(): void
    {
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
                        'name' => 'video.mp4',
                        'id' => 'id:video123',
                        'size' => 2048,
                        'path_lower' => '/video.mp4',
                    ],
                    [
                        '.tag' => 'file',
                        'name' => 'document.pdf',
                        'id' => 'id:pdf123',
                        'size' => 512,
                        'path_lower' => '/document.pdf',
                    ],
                    [
                        '.tag' => 'file',
                        'name' => 'unsupported.exe',
                        'id' => 'id:exe123',
                        'size' => 4096,
                        'path_lower' => '/unsupported.exe',
                    ]
                ],
                'has_more' => false,
            ], 200),
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.projects.dropbox.scan', $this->project), [
                'dropbox_url' => 'https://www.dropbox.com/scl/fo/abc123xyz',
            ]);

        $response->assertOk()
            ->assertJsonCount(3, 'files')
            ->assertJsonPath('counts.images', 1)
            ->assertJsonPath('counts.videos', 1)
            ->assertJsonPath('counts.documents', 1)
            ->assertJsonPath('counts.unsupported', 1);
    }

    public function test_scan_detects_pre_download_duplicates(): void
    {
        // Insert a duplicate record manually
        ClientProjectMedia::create([
            'client_project_id' => $this->project->id,
            'uploaded_by' => $this->admin->id,
            'type' => 'image',
            'delivery_stage' => 'raw',
            'disk' => 'public',
            'path' => 'media/test-project/raw-footage/owner/photo.jpg',
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
                        'name' => 'new-photo.jpg',
                        'id' => 'id:newfile456',
                        'size' => 2048,
                        'path_lower' => '/new-photo.jpg',
                    ]
                ],
                'has_more' => false,
            ], 200),
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.projects.dropbox.scan', $this->project), [
                'dropbox_url' => 'https://www.dropbox.com/scl/fo/abc123xyz',
            ]);

        $response->assertOk()
            ->assertJsonPath('files.0.is_duplicate', true)
            ->assertJsonPath('files.1.is_duplicate', false);
    }

    public function test_import_downloads_and_saves_media_with_source_info(): void
    {
        $fakeFileContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');

        Http::fake([
            'content.dropboxapi.com/2/sharing/get_shared_link_file' => Http::response($fakeFileContent, 200),
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.projects.dropbox.import-file', $this->project), [
                'dropbox_url' => 'https://www.dropbox.com/scl/fo/abc123xyz',
                'file_id' => 'id:file123',
                'name' => 'imported-photo.jpg',
                'path' => '/imported-photo.jpg',
                'size' => strlen($fakeFileContent),
                'type' => 'image',
                'media_stage' => 'raw',
            ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('client_project_media', [
            'client_project_id' => $this->project->id,
            'original_name' => 'imported-photo.jpg',
            'dropbox_file_id' => 'id:file123',
            'file_hash' => hash('sha256', $fakeFileContent),
            'dropbox_shared_link' => 'https://www.dropbox.com/scl/fo/abc123xyz',
            'import_source' => 'dropbox',
        ]);

        $mediaItem = ClientProjectMedia::query()->latest('id')->first();
        Storage::disk($mediaItem->disk)->assertExists($mediaItem->path);
    }

    public function test_import_detects_post_download_hash_duplicates(): void
    {
        $fakeFileContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
        $fakeHash = hash('sha256', $fakeFileContent);

        // Pre-insert duplicate with same hash
        ClientProjectMedia::create([
            'client_project_id' => $this->project->id,
            'uploaded_by' => $this->admin->id,
            'type' => 'image',
            'delivery_stage' => 'raw',
            'disk' => 'public',
            'path' => 'media/test-project/raw-footage/owner/duplicate.jpg',
            'original_name' => 'different-name.jpg',
            'size_bytes' => strlen($fakeFileContent),
            'file_hash' => $fakeHash,
        ]);

        Http::fake([
            'content.dropboxapi.com/2/sharing/get_shared_link_file' => Http::response($fakeFileContent, 200),
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.projects.dropbox.import-file', $this->project), [
                'dropbox_url' => 'https://www.dropbox.com/scl/fo/abc123xyz',
                'file_id' => 'id:file123',
                'name' => 'imported-photo.jpg',
                'path' => '/imported-photo.jpg',
                'size' => strlen($fakeFileContent),
                'type' => 'image',
                'media_stage' => 'raw',
            ]);

        $response->assertOk()
            ->assertJsonPath('status', 'skipped')
            ->assertJsonPath('message', 'Duplicate file detected by file content hash.');
    }

    public function test_import_rejects_executable_files(): void
    {
        $fakeFileContent = 'fake php code';

        Http::fake([
            'content.dropboxapi.com/2/sharing/get_shared_link_file' => Http::response($fakeFileContent, 200),
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.projects.dropbox.import-file', $this->project), [
                'dropbox_url' => 'https://www.dropbox.com/scl/fo/abc123xyz',
                'file_id' => 'id:exe123',
                'name' => 'script.php',
                'path' => '/script.php',
                'size' => strlen($fakeFileContent),
                'type' => 'document',
                'media_stage' => 'document',
            ]);

        $response->assertStatus(400)
            ->assertJsonPath('error', 'Executable uploads are strictly prohibited for security reasons.');
    }

    public function test_import_saves_zip_file_correctly_based_on_stage(): void
    {
        Http::fake([
            'content.dropboxapi.com/2/sharing/get_shared_link_file' => Http::sequence()
                ->push('fake zip archive 1', 200)
                ->push('fake zip archive 2', 200),
        ]);

        // Upload ZIP as Raw Footage
        $responseRaw = $this->actingAs($this->admin)
            ->postJson(route('admin.projects.dropbox.import-file', $this->project), [
                'dropbox_url' => 'https://www.dropbox.com/scl/fo/abc123xyz',
                'file_id' => 'id:zip1',
                'name' => 'raw-archive.zip',
                'path' => '/raw-archive.zip',
                'size' => strlen('fake zip archive 1'),
                'type' => 'document',
                'media_stage' => 'raw',
            ]);

        $responseRaw->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('client_project_media', [
            'client_project_id' => $this->project->id,
            'original_name' => 'raw-archive.zip',
            'type' => 'raw_zip',
            'delivery_stage' => 'raw',
        ]);

        // Upload ZIP as Document
        $responseDoc = $this->actingAs($this->admin)
            ->postJson(route('admin.projects.dropbox.import-file', $this->project), [
                'dropbox_url' => 'https://www.dropbox.com/scl/fo/abc123xyz',
                'file_id' => 'id:zip2',
                'name' => 'final-archive.zip',
                'path' => '/final-archive.zip',
                'size' => strlen('fake zip archive 2'),
                'type' => 'document',
                'media_stage' => 'document',
            ]);

        $responseDoc->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('client_project_media', [
            'client_project_id' => $this->project->id,
            'original_name' => 'final-archive.zip',
            'type' => 'final_zip',
            'delivery_stage' => 'final_zip',
        ]);
    }
}
