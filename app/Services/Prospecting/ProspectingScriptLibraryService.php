<?php

namespace App\Services\Prospecting;

use App\Models\ProspectingScript;

class ProspectingScriptLibraryService
{
    /**
     * @return array<int, array{id: string, db_id: int, name: string, content: string, sort_order: int, is_private: bool}>
     */
    public function scriptsForProspectingTool(?int $accountId = null, ?int $userId = null): array
    {
        $query = ProspectingScript::query()
            ->where('is_active', true)
            ->when($accountId === null, function ($query): void {
                $query->whereNull('account_id')
                    ->whereNull('user_id');
            })
            ->when($accountId !== null, function ($query) use ($accountId, $userId): void {
                $query->where(function ($innerQuery) use ($accountId): void {
                    $innerQuery->whereNull('account_id')
                        ->orWhere('account_id', $accountId);
                });

                if ($userId !== null) {
                    $query->where(function ($innerQuery) use ($userId): void {
                        $innerQuery->whereNull('user_id')
                            ->orWhere('user_id', $userId);
                    });
                } else {
                    $query->whereNull('user_id');
                }
            })
            ->orderBy('sort_order')
            ->orderBy('id');

        return $query->get()
            ->map(function (ProspectingScript $script): array {
                return [
                    'id' => 'script-'.$script->id,
                    'db_id' => (int) $script->id,
                    'name' => $script->name,
                    'content' => $script->content,
                    'sort_order' => $script->sort_order,
                    'is_private' => $script->user_id !== null,
                ];
            })
            ->all();
    }
}
