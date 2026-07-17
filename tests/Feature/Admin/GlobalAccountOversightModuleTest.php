<?php

namespace Tests\Feature\Admin;

use App\Models\Account;
use App\Models\PlanModuleVisibility;
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
        $response->assertSee('Prospecting Scripts');
        $response->assertSee('Analytics');
        $response->assertSee('Marketing');
    }

    public function test_non_global_admin_sidebar_hides_global_account_oversight_module_link(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertDontSee('Global Account Oversight');
        $response->assertDontSee('Prospecting Scripts');
        $response->assertDontSee('Analytics');
        $response->assertDontSee('Marketing');
    }

    public function test_global_admin_oversight_shows_fallback_when_plan_has_no_enabled_modules(): void
    {
        $account = Account::factory()->create([
            'service_level' => Account::SERVICE_LEVEL_SINGLE_AGENT,
        ]);

        User::factory()->create([
            'role' => 'admin',
            'account_id' => $account->id,
        ]);

        foreach ([
            'lead_management',
            'prospecting_tool',
            'events',
            'email_templates',
            'user_management',
        ] as $moduleKey) {
            PlanModuleVisibility::query()->updateOrCreate(
                [
                    'service_level' => Account::SERVICE_LEVEL_SINGLE_AGENT,
                    'module_key' => $moduleKey,
                ],
                [
                    'is_enabled' => false,
                ],
            );
        }

        $globalAdmin = User::factory()->create(['role' => 'global_admin']);

        $response = $this->actingAs($globalAdmin)->get(route('admin.global-account-oversight.index'));

        $response->assertOk();
        $response->assertSee('Enabled Modules');
        $response->assertSee('No modules enabled for this plan.');
    }
}