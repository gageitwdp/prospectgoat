<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEventRequest;
use App\Http\Requests\Admin\UpdateEventRequest;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(): View
    {
        $accountId = $this->requireCurrentAccountId();

        $events = Event::query()
            ->where('account_id', $accountId)
            ->latest('event_time')
            ->paginate(15)
            ->withQueryString();

        return view('admin.events.index', compact('events'));
    }

    public function create(): View
    {
        return view('admin.events.create');
    }

    public function store(StoreEventRequest $request): RedirectResponse
    {
        $accountId = $this->requireCurrentAccountId();
        $data = $request->validated();

        Event::create([
            'account_id' => $accountId,
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['slug'] ?? $data['name'], null, $accountId),
            'location' => $data['location'],
            'event_time' => $data['event_time'],
            'details' => $data['details'] ?? null,
            'status' => $data['status'],
        ]);

        return redirect()
            ->route('admin.events.index')
            ->with('status', 'Event created successfully.');
    }

    public function edit(Event $event): View
    {
        abort_unless($event->account_id === $this->requireCurrentAccountId(), 404);

        return view('admin.events.edit', compact('event'));
    }

    public function update(UpdateEventRequest $request, Event $event): RedirectResponse
    {
        $accountId = $this->requireCurrentAccountId();
        abort_unless($event->account_id === $accountId, 404);

        $data = $request->validated();

        $event->update([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['slug'] ?? $data['name'], $event->id, $accountId),
            'location' => $data['location'],
            'event_time' => $data['event_time'],
            'details' => $data['details'] ?? null,
            'status' => $data['status'],
        ]);

        return redirect()
            ->route('admin.events.index')
            ->with('status', 'Event updated successfully.');
    }

    private function uniqueSlug(string $value, ?int $ignoreEventId = null, ?int $accountId = null): string
    {
        $accountId ??= $this->requireCurrentAccountId();
        $base = Str::slug($value);
        $slug = $base === '' ? 'event' : $base;
        $counter = 1;

        while (Event::query()
            ->where('account_id', $accountId)
            ->where('slug', $slug)
            ->when($ignoreEventId !== null, fn ($query) => $query->where('id', '!=', $ignoreEventId))
            ->exists()) {
            $slug = sprintf('%s-%d', $base === '' ? 'event' : $base, $counter);
            $counter++;
        }

        return $slug;
    }
}
