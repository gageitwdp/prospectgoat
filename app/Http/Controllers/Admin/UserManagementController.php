<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(): View
    {
        $accountId = $this->requireCurrentAccountId();

        $users = User::query()
            ->where('account_id', $accountId)
            ->orderBy('role')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        return view('admin.users.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $accountId = $this->requireCurrentAccountId();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'role' => ['required', 'in:owner,admin,manager,agent'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'notify_on_new_lead_intake' => ['sometimes', 'boolean'],
            'notify_on_lead_assignment' => ['sometimes', 'boolean'],
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'account_id' => $accountId,
            'role' => $data['role'],
            'password' => $data['password'],
            'notify_on_new_lead_intake' => (bool) ($data['notify_on_new_lead_intake'] ?? false),
            'notify_on_lead_assignment' => (bool) ($data['notify_on_lead_assignment'] ?? false),
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'User created successfully.');
    }

    public function edit(User $user): View
    {
        abort_unless($user->account_id === $this->requireCurrentAccountId(), 404);

        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->account_id === $this->requireCurrentAccountId(), 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', 'in:owner,admin,manager,agent'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'notify_on_new_lead_intake' => ['sometimes', 'boolean'],
            'notify_on_lead_assignment' => ['sometimes', 'boolean'],
        ]);

        if ($request->user()->id === $user->id && ! in_array($data['role'], ['owner', 'admin'], true)) {
            return redirect()
                ->route('admin.users.edit', $user)
                ->with('status', 'You cannot remove admin access from your own account.');
        }

        $updateData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'notify_on_new_lead_intake' => (bool) ($data['notify_on_new_lead_intake'] ?? false),
            'notify_on_lead_assignment' => (bool) ($data['notify_on_lead_assignment'] ?? false),
        ];

        if (! empty($data['password'])) {
            $updateData['password'] = $data['password'];
        }

        $user->update($updateData);

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'User updated successfully.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->account_id === $this->requireCurrentAccountId(), 404);

        if ($request->user()->id === $user->id) {
            return redirect()
                ->route('admin.users.index')
                ->with('status', 'You cannot delete your own user record.');
        }

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'User deleted successfully.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $accountId = $this->requireCurrentAccountId();

        $data = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $currentUserId = (int) $request->user()->id;

        $userIds = collect($data['user_ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->reject(fn ($id) => $id === $currentUserId)
            ->values();

        if ($userIds->isEmpty()) {
            return redirect()
                ->route('admin.users.index')
                ->with('status', 'No users deleted. Your own account cannot be bulk deleted.');
        }

        User::query()->where('account_id', $accountId)->whereIn('id', $userIds)->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('status', sprintf('%d users deleted successfully.', $userIds->count()));
    }
}
