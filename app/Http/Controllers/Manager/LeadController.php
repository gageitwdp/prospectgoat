<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateLeadRequest;
use App\Models\Lead;
use App\Models\User;
use App\Notifications\LeadAssignmentChangedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LeadController extends Controller
{
    public function pipeline(Request $request): View
    {
        $statuses = ['new', 'contacted', 'qualified', 'active', 'closed'];
        $periods = ['7', '30', '90', 'all'];
        $period = $request->string('period')->toString();

        if (! in_array($period, $periods, true)) {
            $period = '30';
        }

        $query = Lead::query()->with('assignedManager');

        if ($period !== 'all') {
            $query->where('created_at', '>=', now()->subDays((int) $period));
        }

        $leads = $query
            ->latest()
            ->get();

        $leadGroups = $leads->groupBy('status');

        $totalLeads = $leads->count();
        $closedLeads = $leadGroups->get('closed', collect())->count();
        $activeLeads = $totalLeads - $closedLeads;
        $closeRate = $totalLeads > 0 ? (int) round(($closedLeads / $totalLeads) * 100) : 0;

        $openLeads = $leads->filter(fn (Lead $lead) => $lead->status !== 'closed');
        $averageOpenDays = $openLeads->count() > 0
            ? (int) round($openLeads->avg(fn (Lead $lead) => $lead->created_at->diffInDays(now())))
            : 0;

        $metrics = [
            'total' => $totalLeads,
            'active' => $activeLeads,
            'closed' => $closedLeads,
            'close_rate' => $closeRate,
            'avg_open_days' => $averageOpenDays,
        ];

        return view('manager.leads.pipeline', compact('statuses', 'leadGroups', 'metrics', 'period', 'periods'));
    }

    public function index(Request $request): View
    {
        $visibility = $request->string('visibility')->toString();

        $query = Lead::query()->with('assignedManager');

        if ($visibility === 'deleted') {
            $query->onlyTrashed();
        } elseif ($visibility === 'all') {
            $query->withTrashed();
        }

        $leads = $query
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('lead_type'), fn ($q) => $q->where('lead_type', $request->string('lead_type')))
            ->when($request->filled('source'), fn ($q) => $q->where('source', $request->string('source')))
            ->when($request->filled('assigned_to'), fn ($q) => $q->where('assigned_to', $request->integer('assigned_to')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $managers = User::query()
            ->whereIn('role', ['admin', 'agent'])
            ->orderBy('name')
            ->get(['id', 'name', 'role']);

        return view('manager.leads.index', compact('leads', 'managers', 'visibility'));
    }

    public function show(Lead $lead): View
    {
        $lead->load([
            'assignedManager',
            'activities' => fn ($query) => $query->latest('created_at'),
            'tasks' => fn ($query) => $query->orderBy('status')->orderBy('due_date'),
        ]);

        $managers = User::query()
            ->whereIn('role', ['admin', 'agent'])
            ->orderBy('name')
            ->get(['id', 'name', 'role']);

        return view('manager.leads.show', compact('lead', 'managers'));
    }

    public function update(UpdateLeadRequest $request, Lead $lead): RedirectResponse
    {
        $data = $request->validated();

        $this->ensureValidTransition($lead->status, $data['status']);

        $originalStatus = $lead->status;
        $originalAssigned = $lead->assigned_to;

        $lead->update($data);

        if ($originalStatus !== $lead->status) {
            $lead->activities()->create([
                'type' => 'note',
                'description' => sprintf('Lead status changed from %s to %s.', $originalStatus, $lead->status),
            ]);
        }

        if ($originalAssigned !== $lead->assigned_to) {
            $from = $originalAssigned ? User::find($originalAssigned)?->name : 'Unassigned';
            $to = $lead->assignedManager?->name ?? 'Unassigned';

            $lead->activities()->create([
                'type' => 'note',
                'description' => sprintf('Lead assignment changed from %s to %s.', $from, $to),
            ]);

            if ($lead->assignedManager && $lead->assignedManager->notify_on_lead_assignment) {
                $lead->assignedManager->notify(new LeadAssignmentChangedNotification(
                    $lead,
                    $from,
                    auth()->user()?->name,
                ));
            }
        }

        return back()->with('status', 'Lead updated successfully.');
    }

    public function moveStatus(Request $request, Lead $lead): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:new,contacted,qualified,active,closed'],
        ]);

        $this->ensureValidTransition($lead->status, $data['status']);

        $originalStatus = $lead->status;
        $lead->update(['status' => $data['status']]);

        if ($originalStatus !== $lead->status) {
            $lead->activities()->create([
                'type' => 'note',
                'description' => sprintf('Lead status changed from %s to %s.', $originalStatus, $lead->status),
            ]);
        }

        return back()->with('status', 'Lead stage updated.');
    }

    public function destroy(Request $request, Lead $lead): RedirectResponse
    {
        abort_unless($request->user()?->role === 'admin', 403);

        $lead->delete();

        return redirect()
            ->route('manager.leads.index')
            ->with('status', 'Lead moved to recycle bin.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->role === 'admin', 403);

        $data = $request->validate([
            'lead_ids' => ['required', 'array', 'min:1'],
            'lead_ids.*' => ['integer', 'exists:leads,id'],
        ]);

        $leadIds = collect($data['lead_ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        DB::transaction(function () use ($leadIds): void {
            Lead::query()->whereIn('id', $leadIds)->delete();
        });

        return redirect()
            ->route('manager.leads.index')
            ->with('status', sprintf('%d leads moved to recycle bin.', $leadIds->count()));
    }

    public function restore(Request $request, int $leadId): RedirectResponse
    {
        abort_unless($request->user()?->role === 'admin', 403);

        $lead = Lead::query()->withTrashed()->findOrFail($leadId);

        if (! $lead->trashed()) {
            return redirect()
                ->route('manager.leads.index')
                ->with('status', 'Lead is already active.');
        }

        $lead->restore();

        return redirect()
            ->route('manager.leads.index', ['visibility' => 'deleted'])
            ->with('status', 'Lead restored successfully.');
    }

    public function bulkRestore(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->role === 'admin', 403);

        $data = $request->validate([
            'lead_ids' => ['required', 'array', 'min:1'],
            'lead_ids.*' => ['integer', 'exists:leads,id'],
        ]);

        $leadIds = collect($data['lead_ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $restored = Lead::query()
            ->onlyTrashed()
            ->whereIn('id', $leadIds)
            ->restore();

        return redirect()
            ->route('manager.leads.index', ['visibility' => 'deleted'])
            ->with('status', sprintf('%d leads restored successfully.', $restored));
    }

    private function ensureValidTransition(string $from, string $to): void
    {
        if ($from === $to) {
            return;
        }

        $allowed = [
            'new' => ['contacted'],
            'contacted' => ['qualified', 'closed'],
            'qualified' => ['active', 'closed'],
            'active' => ['closed'],
            'closed' => [],
        ];

        if (! in_array($to, $allowed[$from] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => sprintf('Invalid status transition from %s to %s.', $from, $to),
            ]);
        }
    }
}
