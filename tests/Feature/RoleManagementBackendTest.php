<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class RoleManagementBackendTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_assigns_client_role_and_logs_in_immediately(): void
    {
        // Seed an admin first so the next signup gets the client role
        User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $response = $this->post(route('signup.store'), [
            'name' => 'John Client',
            'email' => 'john.client@example.com',
            'phone' => '1234567890',
            'password' => 'K3p9-Wz2a_M7q2_Xy1s!',
            'password_confirmation' => 'K3p9-Wz2a_M7q2_Xy1s!',
        ]);

        $response->assertRedirect(route('user.dashboard'));

        $this->assertDatabaseHas('users', [
            'email' => 'john.client@example.com',
            'role' => 'client',
            'status' => 'active',
        ]);

        $user = User::where('email', 'john.client@example.com')->firstOrFail();

        $this->assertDatabaseHas('clients', [
            'user_id' => $user->id,
            'email' => 'john.client@example.com',
        ]);

        $this->assertTrue(Auth::check());
        $this->assertSame($user->id, Auth::id());
        $this->assertNotNull($user->last_login_at);
    }

    public function test_deactivated_users_are_blocked_from_logging_in(): void
    {
        $user = User::factory()->create([
            'email' => 'deactivated@example.com',
            'password' => bcrypt('Password123!'),
            'role' => 'client',
            'status' => 'suspended',
        ]);

        $response = $this->post(route('login.store'), [
            'email' => 'deactivated@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertFalse(Auth::check());
    }

    public function test_admin_can_update_user_role_and_it_creates_audit_log(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $targetUser = User::factory()->create([
            'role' => 'photographer',
            'status' => 'active',
        ]);

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.users.update', $targetUser), [
                'name' => 'Updated Name',
                'email' => $targetUser->email,
                'phone' => '0987654321',
                'role' => 'editor',
                'status' => 'active',
                'reason' => 'Promotion to Editor',
            ]);

        $response->assertRedirect();
        
        $targetUser->refresh();
        $this->assertSame('editor', $targetUser->role);
        $this->assertSame('Updated Name', $targetUser->name);

        $this->assertDatabaseHas('role_change_logs', [
            'actor_id' => $admin->id,
            'user_id' => $targetUser->id,
            'old_role' => 'photographer',
            'new_role' => 'editor',
            'reason' => 'Promotion to Editor',
        ]);
    }

    public function test_unauthorized_users_cannot_change_roles(): void
    {
        $photographer = User::factory()->create([
            'role' => 'photographer',
            'status' => 'active',
        ]);

        $targetUser = User::factory()->create([
            'role' => 'client',
            'status' => 'active',
        ]);

        $response = $this
            ->actingAs($photographer)
            ->post(route('admin.users.update', $targetUser), [
                'name' => 'Malicious Update',
                'email' => $targetUser->email,
                'role' => 'admin',
                'status' => 'active',
            ]);

        $response->assertForbidden();
        $targetUser->refresh();
        $this->assertSame('client', $targetUser->role);
    }

    public function test_safeguard_prevents_demoting_or_deactivating_last_admin(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        // Attempt to demote himself (he is the only active admin)
        $response = $this
            ->actingAs($admin)
            ->post(route('admin.users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => 'client',
                'status' => 'active',
            ]);

        $response->assertSessionHasErrors('role');
        $admin->refresh();
        $this->assertSame('admin', $admin->role);

        // Attempt to deactivate himself
        $response2 = $this
            ->actingAs($admin)
            ->post(route('admin.users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => 'admin',
                'status' => 'suspended',
            ]);

        $response2->assertSessionHasErrors('role');
        $admin->refresh();
        $this->assertSame('active', $admin->status);
    }

    public function test_admin_can_reset_user_password(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $targetUser = User::factory()->create([
            'email' => 'target-client@example.com',
            'role' => 'client',
            'status' => 'active',
        ]);

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.users.reset-password', $targetUser), [
                'password' => 'K3p9-Wz2a_M7q2_Xy1s!',
                'password_confirmation' => 'K3p9-Wz2a_M7q2_Xy1s!',
            ]);

        $response->assertRedirect();
        
        // Logout admin so guest middleware does not intercept
        Auth::logout();

        // Attempt login with new password
        $loginResponse = $this->post(route('login.store'), [
            'email' => 'target-client@example.com',
            'password' => 'K3p9-Wz2a_M7q2_Xy1s!',
        ]);

        $loginResponse->assertRedirect(route('user.dashboard'));
        $this->assertTrue(Auth::check());
    }
}
