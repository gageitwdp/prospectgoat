<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalAccountOversightModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_admin_can_access_global_account_oversight_module(): void
    {
        $globalAdmin = User::factory()->create(['role' => 'global_admin']);

        $response = $this->actingAs($globalAdmin)->get(route('admin.global-account-oversight.index'));

        $response->assertOk();
        $response->assertSee('Global Account Oversight');
    }

    public function test_non_global_admin_cannot_access_global_account_oversight_module(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.global-account-oversight.index'));

        $response->assertForbidden();
    }

    public function test_global_admin_sidebar_shows_global_account_oversight_module_link(): void
    {
        $globalAdmin = User::factory()->create(['role' => 'global_admin']);

        $response = $this->actingAs($globalAdmin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Global Account Oversight');
    }

    public function test_non_global_admin_sidebar_hides_global_account_oversight_module_link(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertDontSee('Global Account Oversight');
    }
}