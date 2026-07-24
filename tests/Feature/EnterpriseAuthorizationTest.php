<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\LoginRecord;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class EnterpriseAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles and permissions since setUp runs fresh database
        $this->seedRolesAndPermissions();
    }

    private function seedRolesAndPermissions(): void
    {
        $roles = ['super_admin', 'admin', 'manager', 'photographer', 'editor', 'client', 'user'];
        foreach ($roles as $r) {
            Role::firstOrCreate(
                ['name' => $r],
                ['display_name' => ucfirst($r)]
            );
        }

        $permissions = ['manage_users', 'manage_projects', 'download_media', 'manage_settings'];
        foreach ($permissions as $p) {
            Permission::firstOrCreate(
                ['name' => $p],
                ['display_name' => ucfirst(str_replace('_', ' ', $p))]
            );
        }

        // super_admin & admin get all
        $allPerms = Permission::all();
        Role::where('name', 'super_admin')->first()->permissions()->sync($allPerms);
        Role::where('name', 'admin')->first()->permissions()->sync($allPerms);
        Role::where('name', 'client')->first()->permissions()->sync([Permission::where('name', 'download_media')->first()->id]);
    }

    public function test_public_registration_assigns_client_role_and_creates_profile_and_logs_login_record(): void
    {
        // First admin exists
        User::factory()->create([
            'role' => 'admin',
            'role_id' => Role::where('name', 'admin')->first()->id,
            'status' => 'active',
        ]);

        $response = $this->post(route('signup.store'), [
            'name' => 'Bob Test',
            'email' => 'bob@example.com',
            'password' => 'K3p9-Wz2a_M7q2_Xy1s!',
            'password_confirmation' => 'K3p9-Wz2a_M7q2_Xy1s!',
        ]);

        $response->assertRedirect(route('user.dashboard'));

        $this->assertDatabaseHas('users', [
            'email' => 'bob@example.com',
            'role' => 'client',
            'status' => 'active',
        ]);

        $user = User::where('email', 'bob@example.com')->firstOrFail();
        $this->assertSame(Role::where('name', 'client')->first()->id, $user->role_id);

        $this->assertDatabaseHas('clients', [
            'user_id' => $user->id,
            'email' => 'bob@example.com',
        ]);

        $this->assertDatabaseHas('login_records', [
            'user_id' => $user->id,
            'is_success' => true,
        ]);
    }

    public function test_suspended_and_locked_logins_are_blocked_and_logged(): void
    {
        $suspendedUser = User::factory()->create([
            'email' => 'suspended@example.com',
            'password' => bcrypt('Password123!'),
            'role' => 'client',
            'role_id' => Role::where('name', 'client')->first()->id,
            'status' => 'suspended',
        ]);

        $response = $this->post(route('login.store'), [
            'email' => 'suspended@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertFalse(Auth::check());

        $this->assertDatabaseHas('login_records', [
            'user_id' => $suspendedUser->id,
            'is_success' => false,
            'failure_reason' => 'Account status blocked: suspended',
        ]);
    }

    public function test_permission_based_access_works_correctly(): void
    {
        // 1. User with manage_users permission (admin) can access users management
        $admin = User::factory()->create([
            'role' => 'admin',
            'role_id' => Role::where('name', 'admin')->first()->id,
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk();

        // 2. User without manage_users permission (client) gets 403 Forbidden
        $clientUser = User::factory()->create([
            'role' => 'client',
            'role_id' => Role::where('name', 'client')->first()->id,
            'status' => 'active',
        ]);

        // Note: admin.users.index is wrapped under role:admin,owner middleware which client doesn't pass
        $this->actingAs($clientUser)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_permission_matrix_can_be_updated_at_runtime(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'role_id' => Role::where('name', 'admin')->first()->id,
            'status' => 'active',
        ]);

        $clientRole = Role::where('name', 'client')->first();
        $manageProjectsPerm = Permission::where('name', 'manage_projects')->first();

        // Client role currently does NOT have manage_projects permission
        $clientUser = User::factory()->create([
            'role' => 'client',
            'role_id' => $clientRole->id,
            'status' => 'active',
        ]);

        $this->assertFalse($clientUser->hasPermission('manage_projects'));

        // Post role-permission matrix configuration
        $response = $this->actingAs($admin)
            ->post(route('admin.permissions.update'), [
                'matrix' => [
                    $clientRole->id => [
                        $manageProjectsPerm->id => $manageProjectsPerm->id,
                    ]
                ]
            ]);

        $response->assertRedirect();

        // Client role has permission now
        $clientUser->refresh();
        $this->assertTrue($clientUser->hasPermission('manage_projects'));
    }

    public function test_bulk_actions_works_safely(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'role_id' => Role::where('name', 'admin')->first()->id,
            'status' => 'active',
        ]);

        $users = User::factory()->count(3)->create([
            'role' => 'user',
            'role_id' => Role::where('name', 'user')->first()->id,
            'status' => 'active',
        ]);

        $userIds = $users->pluck('id')->toArray();

        // Bulk Suspend
        $response = $this->actingAs($admin)
            ->post(route('admin.users.bulk'), [
                'user_ids' => $userIds,
                'action' => 'suspend',
            ]);

        $response->assertRedirect();
        foreach ($users as $u) {
            $u->refresh();
            $this->assertSame('suspended', $u->status);
        }

        // Bulk Activate
        $response2 = $this->actingAs($admin)
            ->post(route('admin.users.bulk'), [
                'user_ids' => $userIds,
                'action' => 'activate',
            ]);

        $response2->assertRedirect();
        foreach ($users as $u) {
            $u->refresh();
            $this->assertSame('active', $u->status);
        }

        // Bulk Change Role
        $response3 = $this->actingAs($admin)
            ->post(route('admin.users.bulk'), [
                'user_ids' => $userIds,
                'action' => 'change_role',
                'bulk_role' => 'photographer',
            ]);

        $response3->assertRedirect();
        $photographerRoleId = Role::where('name', 'photographer')->first()->id;
        foreach ($users as $u) {
            $u->refresh();
            $this->assertSame('photographer', $u->role);
            $this->assertSame($photographerRoleId, $u->role_id);
        }
    }

    public function test_bulk_suspend_prevented_if_it_affects_last_active_admin(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'role_id' => Role::where('name', 'admin')->first()->id,
            'status' => 'active',
        ]);

        // Attempting to bulk suspend this admin
        $response = $this->actingAs($admin)
            ->post(route('admin.users.bulk'), [
                'user_ids' => [$admin->id],
                'action' => 'suspend',
            ]);

        $response->assertSessionHasErrors('bulk');
        $admin->refresh();
        $this->assertSame('active', $admin->status);
    }
}
