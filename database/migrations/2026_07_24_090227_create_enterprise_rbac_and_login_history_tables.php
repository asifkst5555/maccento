<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create roles table
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('display_name', 100);
            $table->string('description', 255)->nullable();
            $table->timestamps();
        });

        // 2. Create permissions table
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('display_name', 100);
            $table->string('description', 255)->nullable();
            $table->timestamps();
        });

        // 3. Create pivot table
        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });

        // 4. Modify users table
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('role')->constrained('roles')->nullOnDelete();
            $table->softDeletes()->after('last_login_at');
        });

        // 5. Create login_records table
        Schema::create('login_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('email', 255)->nullable()->index();
            $table->timestamp('login_at')->nullable();
            $table->timestamp('logout_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->string('browser', 100)->nullable();
            $table->string('operating_system', 100)->nullable();
            $table->string('device', 100)->nullable();
            $table->boolean('is_success')->default(true);
            $table->string('failure_reason', 255)->nullable();
            $table->timestamps();
        });

        // 6. Seed initial Roles
        $roleData = [
            ['name' => 'super_admin', 'display_name' => 'Super Admin', 'description' => 'System owner with absolute authority.'],
            ['name' => 'admin', 'display_name' => 'Administrator', 'description' => 'Business manager and accounts coordinator.'],
            ['name' => 'manager', 'display_name' => 'Manager', 'description' => 'Client lead and staff coordinator.'],
            ['name' => 'photographer', 'display_name' => 'Photographer', 'description' => 'Assigned staff content creator.'],
            ['name' => 'editor', 'display_name' => 'Editor', 'description' => 'Assigned staff post-processing editor.'],
            ['name' => 'client', 'display_name' => 'Client', 'description' => 'End-user client portal access.'],
            ['name' => 'user', 'display_name' => 'User', 'description' => 'Standard user account fallback.'],
        ];

        foreach ($roleData as $role) {
            \DB::table('roles')->insert(array_merge($role, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        // 7. Seed initial Permissions
        $permissions = [
            ['name' => 'manage_users', 'display_name' => 'Manage Users', 'description' => 'Create, edit, suspend, and delete users.'],
            ['name' => 'manage_roles', 'display_name' => 'Manage Roles', 'description' => 'Edit roles and change user roles.'],
            ['name' => 'manage_projects', 'display_name' => 'Manage Projects', 'description' => 'Create, update, and manage client projects.'],
            ['name' => 'manage_clients', 'display_name' => 'Manage Clients', 'description' => 'Manage client database and directories.'],
            ['name' => 'manage_media', 'display_name' => 'Manage Media', 'description' => 'Full file uploads and organization.'],
            ['name' => 'delete_media', 'display_name' => 'Delete Media', 'description' => 'Permanently delete media assets.'],
            ['name' => 'upload_media', 'display_name' => 'Upload Media', 'description' => 'Upload gallery files and documents.'],
            ['name' => 'download_media', 'display_name' => 'Download Media', 'description' => 'Download high-res media files.'],
            ['name' => 'approve_projects', 'display_name' => 'Approve Projects', 'description' => 'Mark projects as complete and close timelines.'],
            ['name' => 'manage_storage', 'display_name' => 'Manage Storage', 'description' => 'Configure storage backends and bucket mounts.'],
            ['name' => 'manage_settings', 'display_name' => 'Manage Settings', 'description' => 'Modify business and portal configurations.'],
            ['name' => 'manage_notifications', 'display_name' => 'Manage Notifications', 'description' => 'Read and send system notifications.'],
            ['name' => 'view_reports', 'display_name' => 'View Reports', 'description' => 'View business stats and analytics.'],
            ['name' => 'manage_dropbox', 'display_name' => 'Manage Dropbox', 'description' => 'Configure Dropbox folders and integration settings.'],
            ['name' => 'manage_imports', 'display_name' => 'Manage Imports', 'description' => 'Run folder imports from external sources.'],
            ['name' => 'manage_exports', 'display_name' => 'Manage Exports', 'description' => 'Run bulk data and archive exports.'],
        ];

        foreach ($permissions as $perm) {
            \DB::table('permissions')->insert(array_merge($perm, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        // 8. Associate Permissions to Roles
        $rolesMap = \DB::table('roles')->pluck('id', 'name')->all();
        $permsMap = \DB::table('permissions')->pluck('id', 'name')->all();

        // Helper to bind permissions to role
        $bind = function (string $roleName, array $permNames) use ($rolesMap, $permsMap): void {
            if (!isset($rolesMap[$roleName])) return;
            $roleId = $rolesMap[$roleName];
            foreach ($permNames as $pName) {
                if (isset($permsMap[$pName])) {
                    \DB::table('role_has_permissions')->insert([
                        'role_id' => $roleId,
                        'permission_id' => $permsMap[$pName],
                    ]);
                }
            }
        };

        // super_admin & admin get all
        $bind('super_admin', array_keys($permsMap));
        $bind('admin', array_keys($permsMap));

        // manager gets everything except storage and configurations
        $bind('manager', [
            'manage_users', 'manage_projects', 'manage_clients', 'manage_media', 
            'upload_media', 'download_media', 'approve_projects', 'manage_notifications', 
            'view_reports', 'manage_imports', 'manage_exports', 'manage_dropbox'
        ]);

        // photographer can upload and download media and view assigned projects
        $bind('photographer', ['upload_media', 'download_media']);

        // editor can upload/download media and manage files
        $bind('editor', ['upload_media', 'download_media', 'manage_media']);

        // client can download media
        $bind('client', ['download_media']);

        // 9. Backfill existing users to point to role IDs
        $users = \DB::table('users')->get();
        foreach ($users as $user) {
            $userRole = strtolower(trim((string) $user->role));
            // map owner role to super_admin for backfill
            if ($userRole === 'owner') {
                $userRole = 'super_admin';
            }
            $targetRoleId = $rolesMap[$userRole] ?? $rolesMap['user'];
            
            \DB::table('users')->where('id', $user->id)->update([
                'role_id' => $targetRoleId,
                'role' => $userRole, // normalize string column
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_records');

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn(['role_id']);
            $table->dropSoftDeletes();
        });

        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
