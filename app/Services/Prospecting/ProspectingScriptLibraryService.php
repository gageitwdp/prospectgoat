<?php

namespace App\Services\Prospecting;

use App\Models\ProspectingScript;

class ProspectingScriptLibraryService
{
    /**
     * @return array<int, array{id: string, name: string, content: string, sort_order: int}>
     */
    public function scriptsForProspectingTool(): array
    {
        return ProspectingScript::query()
            ->where('is_active', true)
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
