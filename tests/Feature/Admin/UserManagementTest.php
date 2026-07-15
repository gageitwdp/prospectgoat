<?php

namespace Tests\Feature\Admin;

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_create_user_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.users.create'));

        $response->assertOk();
        $response->assertSee('Create User');
    }

    public function test_admin_can_create_user_including_admin_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'New Admin User',
            'email' => 'new-admin@example.com',
            'role' => 'admin',
            'password' => 'StrongPass123',
            'password_confirmation' => 'StrongPass123',
            'notify_on_new_lead_intake' => '1',
            'notify_on_lead_assignment' => '0',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('status', 'User created successfully.');

        $this->assertDatabaseHas('users', [
            'email' => 'new-admin@example.com',
            'role' => 'admin',
            'notify_on_new_lead_intake' => true,
            'notify_on_lead_assignment' => false,
        ]);
    }

    public function test_admin_can_edit_user_email_role_and_reset_password(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $targetUser = User::factory()->create([
            'role' => 'agent',
            'notify_on_new_lead_intake' => false,
            'notify_on_lead_assignment' => false,
        ]);

        $response = $this->actingAs($admin)->put(route('admin.users.update', $targetUser), [
            'name' => 'Updated User',
            'email' => 'updated-user@example.com',
            'role' => 'admin',
            'password' => 'UpdatedPass123',
            'password_confirmation' => 'UpdatedPass123',
            'notify_on_new_lead_intake' => '1',
            'notify_on_lead_assignment' => '1',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('status', 'User updated successfully.');

        $targetUser->refresh();

        $this->assertSame('Updated User', $targetUser->name);
        $this->assertSame('updated-user@example.com', $targetUser->email);
        $this->assertSame('admin', $targetUser->role);
        $this->assertTrue($targetUser->notify_on_new_lead_intake);
        $this->assertTrue($targetUser->notify_on_lead_assignment);
        $this->assertTrue(password_verify('UpdatedPass123', $targetUser->password));
    }

    public function test_admin_can_update_user_without_resetting_password(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $targetUser = User::factory()->create(['role' => 'agent']);
        $originalPassword = $targetUser->password;

        $response = $this->actingAs($admin)->put(route('admin.users.update', $targetUser), [
            'name' => 'Name Only Update',
            'email' => $targetUser->email,
            'role' => 'agent',
            'password' => '',
            'password_confirmation' => '',
            'notify_on_new_lead_intake' => '0',
            'notify_on_lead_assignment' => '0',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $targetUser->refresh();

        $this->assertSame('Name Only Update', $targetUser->name);
        $this->assertSame($originalPassword, $targetUser->password);
    }

    public function test_admin_cannot_demote_self_from_admin_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->put(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => 'agent',
            'password' => '',
            'password_confirmation' => '',
            'notify_on_new_lead_intake' => '1',
            'notify_on_lead_assignment' => '0',
        ]);

        $response->assertRedirect(route('admin.users.edit', $admin));
        $response->assertSessionHas('status', 'You cannot remove admin access from your own account.');
        $this->assertSame('admin', $admin->fresh()->role);
    }

    public function test_admin_can_delete_single_user_record(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $targetUser = User::factory()->create(['role' => 'agent']);

        $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $targetUser));

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('status', 'User deleted successfully.');
        $this->assertDatabaseMissing('users', ['id' => $targetUser->id]);
    }

    public function test_admin_can_bulk_delete_user_records(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $userOne = User::factory()->create(['role' => 'agent']);
        $userTwo = User::factory()->create(['role' => 'agent']);

        $response = $this->actingAs($admin)->delete(route('admin.users.bulk-destroy'), [
            'user_ids' => [$userOne->id, $userTwo->id],
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('status', '2 users deleted successfully.');
        $this->assertDatabaseMissing('users', ['id' => $userOne->id]);
        $this->assertDatabaseMissing('users', ['id' => $userTwo->id]);
    }

    public function test_admin_cannot_delete_own_account_with_single_delete(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $admin));

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('status', 'You cannot delete your own user record.');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_bulk_delete_skips_own_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create(['role' => 'agent']);

        $response = $this->actingAs($admin)->delete(route('admin.users.bulk-destroy'), [
            'user_ids' => [$admin->id, $target->id],
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('status', '1 users deleted successfully.');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    public function test_agent_cannot_delete_users(): void
    {
        $agent = User::factory()->create(['role' => 'agent']);
        $target = User::factory()->create(['role' => 'agent']);

        $singleResponse = $this->actingAs($agent)->delete(route('admin.users.destroy', $target));
        $singleResponse->assertForbidden();

        $bulkResponse = $this->actingAs($agent)->delete(route('admin.users.bulk-destroy'), [
            'user_ids' => [$target->id],
        ]);
        $bulkResponse->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    public function test_agent_cannot_access_create_or_edit_user_pages(): void
    {
        $agent = User::factory()->create(['role' => 'agent']);
        $target = User::factory()->create(['role' => 'agent']);

        $createResponse = $this->actingAs($agent)->get(route('admin.users.create'));
        $createResponse->assertForbidden();

        $editResponse = $this->actingAs($agent)->get(route('admin.users.edit', $target));
        $editResponse->assertForbidden();
    }

    public function test_global_admin_can_view_users_across_all_accounts(): void
    {
        $accountOne = Account::factory()->activeBilling()->create();
        $accountTwo = Account::factory()->activeBilling()->create();

        $globalAdmin = User::factory()->create([
            'role' => 'global_admin',
            'account_id' => $accountOne->id,
        ]);

        $accountOneUser = User::factory()->create([
            'account_id' => $accountOne->id,
            'email' => 'first-account-user@example.com',
        ]);

        $accountTwoUser = User::factory()->create([
            'account_id' => $accountTwo->id,
            'email' => 'second-account-user@example.com',
        ]);

        $response = $this->actingAs($globalAdmin)->get(route('admin.users.index'));

        $response->assertOk();
        $response->assertSee($accountOneUser->email);
        $response->assertSee($accountTwoUser->email);
    }

    public function test_non_global_admin_cannot_assign_global_admin_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Blocked Global Admin',
            'email' => 'blocked-global-admin@example.com',
            'role' => 'global_admin',
            'password' => 'StrongPass123',
            'password_confirmation' => 'StrongPass123',
            'notify_on_new_lead_intake' => '1',
            'notify_on_lead_assignment' => '0',
        ]);

        $response->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', [
            'email' => 'blocked-global-admin@example.com',
        ]);
    }

    public function test_global_admin_can_assign_global_admin_role(): void
    {
        $globalAdmin = User::factory()->create(['role' => 'global_admin']);

        $response = $this->actingAs($globalAdmin)->post(route('admin.users.store'), [
            'name' => 'Second Global Admin',
            'email' => 'second-global-admin@example.com',
            'role' => 'global_admin',
            'password' => 'StrongPass123',
            'password_confirmation' => 'StrongPass123',
            'notify_on_new_lead_intake' => '1',
            'notify_on_lead_assignment' => '1',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'second-global-admin@example.com',
            'role' => 'global_admin',
        ]);
    }

    public function test_non_global_admin_cannot_promote_user_to_global_admin_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create(['role' => 'agent', 'account_id' => $admin->account_id]);

        $response = $this->actingAs($admin)->put(route('admin.users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'role' => 'global_admin',
            'password' => '',
            'password_confirmation' => '',
            'notify_on_new_lead_intake' => '1',
            'notify_on_lead_assignment' => '0',
        ]);

        $response->assertSessionHasErrors('role');
        $this->assertSame('agent', $target->fresh()->role);
    }

    public function test_global_admin_can_promote_user_to_global_admin_role(): void
    {
        $globalAdmin = User::factory()->create(['role' => 'global_admin']);
        $target = User::factory()->create(['role' => 'agent', 'account_id' => $globalAdmin->account_id]);

        $response = $this->actingAs($globalAdmin)->put(route('admin.users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'role' => 'global_admin',
            'password' => '',
            'password_confirmation' => '',
            'notify_on_new_lead_intake' => '1',
            'notify_on_lead_assignment' => '1',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertSame('global_admin', $target->fresh()->role);
    }
}
