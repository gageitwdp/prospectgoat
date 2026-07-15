<?php

namespace Tests\Feature\Admin;

use App\Models\Account;
use App\Models\User;
use App\Services\Plans\PlanModuleVisibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanModuleVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_admin_can_view_plan_module_visibility_screen(): void
    {
        $globalAdmin = User::factory()->create(['role' => User::ROLE_GLOBAL_ADMIN]);

        $response = $this->actingAs($globalAdmin)->get(route('admin.plan-module-visibility.index'));

        $response->assertOk();
        $response->assertSee('Plan Module Visibility');
    }

    public function test_non_global_admin_cannot_view_plan_module_visibility_screen(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->get(route('admin.plan-module-visibility.index'));

        $response->assertForbidden();
    }

    public function test_disabling_module_for_plan_hides_sidebar_link_and_blocks_route_for_that_plan(): void
    {
        $globalAdmin = User::factory()->create(['role' => User::ROLE_GLOBAL_ADMIN]);
        $account = Account::factory()->activeBilling()->create([
            'service_level' => Account::SERVICE_LEVEL_SINGLE_AGENT,
        ]);
        $admin = User::factory()->create([
            'account_id' => $account->id,
            'role' => User::ROLE_ADMIN,
        ]);

        $service = app(PlanModuleVisibilityService::class);

        $visibility = [];
        foreach (array_keys($service->moduleDefinitions()) as $moduleKey) {
            foreach (array_keys($service->serviceLevels()) as $serviceLevel) {
                $visibility[$moduleKey][$serviceLevel] = true;
            }
        }

        $visibility['events'][Account::SERVICE_LEVEL_SINGLE_AGENT] = false;

        $this->actingAs($globalAdmin)
            ->put(route('admin.plan-module-visibility.update'), [
                'visibility' => $visibility,
            ])
            ->assertRedirect(route('admin.plan-module-visibility.index'));

        $dashboardResponse = $this->actingAs($admin)->get(route('admin.dashboard'));
        $dashboardResponse->assertOk();
        $dashboardResponse->assertDontSee('Events');

        $eventsResponse = $this->actingAs($admin)->get(route('admin.events.index'));
        $eventsResponse->assertForbidden();
    }

    public function test_global_admin_can_access_disabled_module_route_for_support_workflows(): void
    {
        $globalAdmin = User::factory()->create(['role' => User::ROLE_GLOBAL_ADMIN]);
        $service = app(PlanModuleVisibilityService::class);

        $visibility = [];
        foreach (array_keys($service->moduleDefinitions()) as $moduleKey) {
            foreach (array_keys($service->serviceLevels()) as $serviceLevel) {
                $visibility[$moduleKey][$serviceLevel] = true;
            }
        }

        $visibility['events'][Account::SERVICE_LEVEL_SINGLE_AGENT] = false;

        $this->actingAs($globalAdmin)
            ->put(route('admin.plan-module-visibility.update'), [
                'visibility' => $visibility,
            ])
            ->assertRedirect(route('admin.plan-module-visibility.index'));

        $response = $this->actingAs($globalAdmin)->get(route('admin.events.index'));

        $response->assertOk();
    }
}
