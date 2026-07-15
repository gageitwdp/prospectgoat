<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Plans\PlanModuleVisibilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlanModuleVisibilityController extends Controller
{
    public function __construct(private readonly PlanModuleVisibilityService $planModuleVisibility) {}

    public function index(): View
    {
        abort_unless(auth()->user()?->isGlobalAdmin(), 403);

        return view('admin.plan-module-visibility.index', [
            'moduleMatrix' => $this->planModuleVisibility->matrix(),
            'serviceLevels' => $this->planModuleVisibility->serviceLevels(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isGlobalAdmin(), 403);

        $moduleKeys = array_keys($this->planModuleVisibility->moduleDefinitions());
        $serviceLevels = array_keys($this->planModuleVisibility->serviceLevels());

        $visibility = [];

        foreach ($moduleKeys as $moduleKey) {
            foreach ($serviceLevels as $serviceLevel) {
                $visibility[$moduleKey][$serviceLevel] = $request->boolean('visibility.'.$moduleKey.'.'.$serviceLevel);
            }
        }

        $this->planModuleVisibility->updateVisibility($visibility);

        return redirect()
            ->route('admin.plan-module-visibility.index')
            ->with('status', 'Plan module visibility updated.');
    }
}
