<?php

namespace App\Services\Prospecting;

use App\Models\ProspectingScript;

class ProspectingScriptLibraryService
{
    /**
     * @return array<int, array{id: string, name: string, content: string, sort_order: int}>
     */
    public function scriptsForProspectingTool(?int $accountId = null): array
    {
        return ProspectingScript::query()
            ->where('is_active', true)
            ->when($accountId !== null, function ($query) use ($accountId): void {
                $query->where(function ($innerQuery) use ($accountId): void {
                    $innerQuery->whereNull('account_id')
                        ->orWhere('account_id', $accountId);
                });
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(function (ProspectingScript $script): array {
                return [
                    'id' => 'script-'.$script->id,
                    'name' => $script->name,
                    'content' => $script->content,
                    'sort_order' => $script->sort_order,
                ];
            })
            ->all();
    }
}
