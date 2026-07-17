<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProspectingScript;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProspectingScriptController extends Controller
{
    public function index(): View
    {
        $query = ProspectingScript::query();

        if (! $this->currentUserIsGlobalAdmin()) {
            $query->where('account_id', $this->requireCurrentAccountId());
        }

        $scripts = $query
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('admin.prospecting-scripts.index', [
            'scripts' => $scripts,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'content' => ['required', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $accountId = $this->currentUserIsGlobalAdmin() ? null : $this->requireCurrentAccountId();

        $maxSortOrder = (int) ProspectingScript::query()
            ->when($accountId === null, fn ($query) => $query->whereNull('account_id'))
            ->when($accountId !== null, fn ($query) => $query->where('account_id', $accountId))
            ->max('sort_order');

        ProspectingScript::query()->create([
            'account_id' => $accountId,
            'name' => $data['name'],
            'content' => $data['content'],
            'sort_order' => array_key_exists('sort_order', $data) ? (int) $data['sort_order'] : $maxSortOrder + 1,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        return redirect()
            ->route('admin.prospecting-scripts.index')
            ->with('status', 'Script tab created.');
    }

    public function update(Request $request, ProspectingScript $prospectingScript): RedirectResponse
    {
        abort_unless($this->inCurrentAccountScope($prospectingScript->account_id), 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'content' => ['required', 'string'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $prospectingScript->update([
            'name' => $data['name'],
            'content' => $data['content'],
            'sort_order' => (int) $data['sort_order'],
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        return redirect()
            ->route('admin.prospecting-scripts.index')
            ->with('status', 'Script tab updated.');
    }

    public function destroy(Request $request, ProspectingScript $prospectingScript): RedirectResponse
    {
        abort_unless($this->inCurrentAccountScope($prospectingScript->account_id), 404);

        $prospectingScript->delete();

        return redirect()
            ->route('admin.prospecting-scripts.index')
            ->with('status', 'Script tab deleted.');
    }
}
