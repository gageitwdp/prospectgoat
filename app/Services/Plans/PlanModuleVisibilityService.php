<?php

namespace App\Services\Plans;

use App\Models\Account;
use App\Models\PlanModuleVisibility;

class PlanModuleVisibilityService
{
    /**
     * @return array<string, array{label: string, description: string}>
     */
    public function moduleDefinitions(): array
    {
        return [
            'lead_management' => [
                'label' => 'Lead Management',
                'description' => 'Review leads, monitor pipeline stages, and coordinate follow-up tasks.',
            ],
            'events' => [
                'label' => 'Events',
                'description' => 'Create, edit, and manage event listings and registrations.',
            ],
            'user_management' => [
                'label' => 'User Management',
                'description' => 'Manage owner, manager, and agent access and role assignments.',
            ],
            'lead_import' => [
                'label' => 'Import Leads',
                'description' => 'Download the CSV template and import lead data in bulk.',
            ],
            'prospecting_tool' => [
                'label' => 'Prospecting Tool',
                'description' => 'Review prospect cards and save qualified leads.',
            ],
            'email_templates' => [
                'label' => 'Email Templates',
                'description' => 'Edit and test template-driven outbound and intake emails.',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function serviceLevels(): array
    {
        return [
            Account::SERVICE_LEVEL_SINGLE_AGENT => 'Single Agent',
            Account::SERVICE_LEVEL_TEAM => 'Team',
            Account::SERVICE_LEVEL_BROKERAGE => 'Brokerage',
        ];
    }

    public function isEnabledForServiceLevel(?string $serviceLevel, string $moduleKey): bool
    {
        if (! $serviceLevel || ! array_key_exists($moduleKey, $this->moduleDefinitions())) {
            return true;
        }

        $setting = PlanModuleVisibility::query()
            ->where('service_level', $serviceLevel)
            ->where('module_key', $moduleKey)
            ->first();

        return $setting?->is_enabled ?? true;
    }

    public function isEnabledForAccount(?Account $account, string $moduleKey): bool
    {
        if (! $account) {
            return false;
        }

        return $this->isEnabledForServiceLevel($account->service_level, $moduleKey);
    }

    /**
     * @return array<string, array{label: string, description: string, by_plan: array<string, bool>}>
     */
    public function matrix(): array
    {
        $definitions = $this->moduleDefinitions();
        $serviceLevels = array_keys($this->serviceLevels());

        $stored = PlanModuleVisibility::query()
            ->whereIn('module_key', array_keys($definitions))
            ->whereIn('service_level', $serviceLevels)
            ->get()
            ->keyBy(fn (PlanModuleVisibility $row) => $row->module_key.'|'.$row->service_level);

        $matrix = [];

        foreach ($definitions as $moduleKey => $definition) {
            $byPlan = [];

            foreach ($serviceLevels as $serviceLevel) {
                $byPlan[$serviceLevel] = $stored->get($moduleKey.'|'.$serviceLevel)?->is_enabled ?? true;
            }

            $matrix[$moduleKey] = [
                'label' => $definition['label'],
                'description' => $definition['description'],
                'by_plan' => $byPlan,
            ];
        }

        return $matrix;
    }

    /**
     * @param  array<string, array<string, bool>>  $visibility
     */
    public function updateVisibility(array $visibility): void
    {
        $moduleDefinitions = $this->moduleDefinitions();
        $serviceLevels = array_keys($this->serviceLevels());

        $rows = [];

        foreach ($moduleDefinitions as $moduleKey => $_definition) {
            foreach ($serviceLevels as $serviceLevel) {
                $rows[] = [
                    'module_key' => $moduleKey,
                    'service_level' => $serviceLevel,
                    'is_enabled' => (bool) ($visibility[$moduleKey][$serviceLevel] ?? false),
                    'updated_at' => now(),
                    'created_at' => now(),
                ];
            }
        }

        PlanModuleVisibility::query()->upsert(
            $rows,
            ['service_level', 'module_key'],
            ['is_enabled', 'updated_at'],
        );
    }
}
