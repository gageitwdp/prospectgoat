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

    public function test_non_global_admin_cannot_access_prospecting_scripts_module(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->get(route('admin.prospecting-scripts.index'));

        $response->assertForbidden();
    }

    public function test_global_admin_can_create_update_and_delete_script_tabs(): void
    {
        $globalAdmin = User::factory()->create(['role' => User::ROLE_GLOBAL_ADMIN]);

        $this->actingAs($globalAdmin)
            ->post(route('admin.prospecting-scripts.store'), [
                'name' => 'Circle Prospecting',
                'content' => 'Hello from a new global script.',
                'sort_order' => 5,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.prospecting-scripts.index'));

        $script = ProspectingScript::query()->firstOrFail();

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

        $this->assertDatabaseCount('prospecting_scripts', 0);
    }

    public function test_active_global_scripts_are_rendered_in_prospecting_tool(): void
    {
        ProspectingScript::query()->create([
            'name' => 'Global FSBO',
            'content' => 'Global script text for all accounts.',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        ProspectingScript::query()->create([
            'name' => 'Inactive Script',
            'content' => 'Should not render.',
            'sort_order' => 2,
            'is_active' => false,
        ]);

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->get(route('admin.prospecting.index'));

        $response->assertOk();
        $response->assertSee('Global FSBO');
        $response->assertSee('Global script text for all accounts.');
        $response->assertDontSee('Inactive Script');
    }
}
