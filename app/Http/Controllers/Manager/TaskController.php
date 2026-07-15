<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Models\Lead;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function store(StoreTaskRequest $request, Lead $lead): RedirectResponse
    {
        abort_unless($lead->account_id === $this->requireCurrentAccountId(), 404);

        $task = $lead->tasks()->create([
            'account_id' => $lead->account_id,
            'title' => $request->validated('title'),
            'due_date' => $request->validated('due_date'),
            'status' => $request->validated('status') ?? 'pending',
        ]);

        $lead->activities()->create([
            'account_id' => $lead->account_id,
            'type' => 'note',
            'description' => sprintf('Task created: %s.', $task->title),
        ]);

        return back()->with('status', 'Task created.');
    }

    public function update(Request $request, Lead $lead, Task $task): RedirectResponse
    {
        abort_unless($task->lead_id === $lead->id, 404);
        abort_unless($lead->account_id === $this->requireCurrentAccountId(), 404);
        abort_unless($task->account_id === $lead->account_id, 404);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'due_date' => ['required', 'date'],
            'status' => ['required', 'in:pending,complete'],
        ]);

        $originalStatus = $task->status;
        $task->update($data);

        if ($originalStatus !== $task->status) {
            $lead->activities()->create([
                'account_id' => $lead->account_id,
                'type' => 'note',
                'description' => sprintf('Task "%s" marked as %s.', $task->title, $task->status),
            ]);
        }

        return back()->with('status', 'Task updated.');
    }
}
