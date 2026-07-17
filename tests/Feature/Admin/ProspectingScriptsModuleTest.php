<?php

namespace Tests\Feature\Admin;

use App\Models\ProspectingScript;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProspectingScriptsModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_admin_can_access_prospecting_scripts_module(): void
    {
        $globalAdmin = User::factory()->create(['role' => User::ROLE_GLOBAL_ADMIN]);

        $response = $this->actingAs($globalAdmin)->get(route('admin.prospecting-scripts.index'));

        $response->assertOk();
        $response->assertSee('Prospecting Script Tabs');
    }

    public function test_non_global_admin_can_access_prospecting_scripts_module(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->get(route('admin.prospecting-scripts.index'));

        $response->assertOk();
    }

    public function test_global_admin_can_create_update_and_delete_script_tabs(): void
    {
        $globalAdmin = User::factory()->create(['role' => User::ROLE_GLOBAL_ADMIN]);
        $startingCount = ProspectingScript::query()->count();

        $this->actingAs($globalAdmin)
            ->post(route('admin.prospecting-scripts.store'), [
                'name' => 'Circle Prospecting',
                'content' => 'Hello from a new global script.',
                'sort_order' => 5,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.prospecting-scripts.index'));

        $script = ProspectingScript::query()->where('name', 'Circle Prospecting')->firstOrFail();

        $this->assertSame('Circle Prospecting', $script->name);
        $this->assertTrue($script->is_active);

        $this->actingAs($globalAdmin)
            ->put(route('admin.prospecting-scripts.update', $script), [
                'name' => 'Circle Prospecting Updated',
                'content' => 'Updated global script content.',
                'sort_order' => 3,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.prospecting-scripts.index'));

        $script->refresh();

        $this->assertSame('Circle Prospecting Updated', $script->name);
        $this->assertSame(3, $script->sort_order);

        $this->actingAs($globalAdmin)
            ->delete(route('admin.prospecting-scripts.destroy', $script))
            ->assertRedirect(route('admin.prospecting-scripts.index'));

        $this->assertDatabaseCount('prospecting_scripts', $startingCount);
    }

    public function test_non_global_admin_can_create_update_and_delete_own_script_tabs(): void
    {
        $account = \App\Models\Account::factory()->activeBilling()->create();
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'account_id' => $account->id,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.prospecting-scripts.store'), [
                'name' => 'My Account Script',
                'content' => 'Account-owned script content.',
                'sort_order' => 4,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.prospecting-scripts.index'));

        $script = ProspectingScript::query()->where('name', 'My Account Script')->firstOrFail();
        $this->assertSame($account->id, $script->account_id);

        $this->actingAs($admin)
            ->put(route('admin.prospecting-scripts.update', $script), [
                'name' => 'My Account Script Updated',
                'content' => 'Updated account-owned content.',
                'sort_order' => 7,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.prospecting-scripts.index'));

        $script->refresh();
        $this->assertSame('My Account Script Updated', $script->name);
        $this->assertSame(7, $script->sort_order);

        $this->actingAs($admin)
            ->delete(route('admin.prospecting-scripts.destroy', $script))
            ->assertRedirect(route('admin.prospecting-scripts.index'));

        $this->assertDatabaseMissing('prospecting_scripts', [
            'id' => $script->id,
        ]);
    }

    public function test_non_global_admin_cannot_modify_global_script_tabs(): void
    {
        $account = \App\Models\Account::factory()->activeBilling()->create();
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'account_id' => $account->id,
        ]);

        $globalScript = ProspectingScript::query()->where('name', 'Expired Script')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.prospecting-scripts.update', $globalScript), [
                'name' => 'Blocked Update',
                'content' => 'Blocked content.',
                'sort_order' => 1,
                'is_active' => '1',
            ])
            ->assertNotFound();

        $this->actingAs($admin)
            ->delete(route('admin.prospecting-scripts.destroy', $globalScript))
            ->assertNotFound();
    }

    public function test_global_admin_can_delete_seeded_default_script_tabs(): void
    {
        $globalAdmin = User::factory()->create(['role' => User::ROLE_GLOBAL_ADMIN]);
        $defaultScript = ProspectingScript::query()->where('name', 'Expired Script')->firstOrFail();

        $this->actingAs($globalAdmin)
            ->delete(route('admin.prospecting-scripts.destroy', $defaultScript))
            ->assertRedirect(route('admin.prospecting-scripts.index'));

        $this->assertDatabaseMissing('prospecting_scripts', [
            'id' => $defaultScript->id,
        ]);
    }

    public function test_active_global_scripts_are_rendered_in_prospecting_tool(): void
    {
        ProspectingScript::query()->where('name', 'Expired Script')->delete();
        ProspectingScript::query()->where('name', 'FSBO')->delete();

        ProspectingScript::query()->create([
            'account_id' => null,
            'name' => 'Global FSBO',
            'content' => 'Global script text for all accounts.',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        ProspectingScript::query()->create([
            'account_id' => null,
            'name' => 'Inactive Script',
            'content' => 'Should not render.',
            'sort_order' => 2,
            'is_active' => false,
        ]);

        $account = \App\Models\Account::factory()->activeBilling()->create();

        ProspectingScript::query()->create([
            'account_id' => $account->id,
            'name' => 'Account Script',
            'content' => 'Account-specific script text.',
            'sort_order' => 3,
            'is_active' => true,
        ]);

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'account_id' => $account->id,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.prospecting.index'));

        $response->assertOk();
        $response->assertSee('Global FSBO');
        $response->assertSee('Global script text for all accounts.');
        $response->assertSee('Account Script');
        $response->assertSee('Account-specific script text.');
        $response->assertDontSee('Inactive Script');
    }
}
