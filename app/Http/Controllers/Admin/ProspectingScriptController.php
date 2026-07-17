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
        abort_unless(auth()->user()?->isGlobalAdmin(), 403);

        $scripts = ProspectingScript::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('admin.prospecting-scripts.index', [
            'scripts' => $scripts,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isGlobalAdmin(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'content' => ['required', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $maxSortOrder = (int) ProspectingScript::query()->max('sort_order');

        ProspectingScript::query()->create([
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
        abort_unless($request->user()?->isGlobalAdmin(), 403);

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
        abort_unless($request->user()?->isGlobalAdmin(), 403);

        $prospectingScript->delete();

        return redirect()
            ->route('admin.prospecting-scripts.index')
            ->with('status', 'Script tab deleted.');
    }
}
