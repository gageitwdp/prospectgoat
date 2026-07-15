<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $userQuery = User::query();
        $leadQuery = Lead::query();

        if (! $this->currentUserIsGlobalAdmin()) {
            $accountId = $this->requireCurrentAccountId();
            $userQuery->where('account_id', $accountId);
            $leadQuery->where('account_id', $accountId);
        }

        $metrics = [
            'total_users' => (clone $userQuery)->count(),
            'admin_users' => (clone $userQuery)->whereIn('role', ['owner', 'admin', 'global_admin'])->count(),
            'manager_users' => (clone $userQuery)->where('role', 'manager')->count(),
            'agent_users' => (clone $userQuery)->where('role', 'agent')->count(),
            'total_leads' => (clone $leadQuery)->count(),
        ];

        $modules = [
            [
                'name' => 'Lead Management',
                'description' => 'Review leads, monitor pipeline stages, and coordinate follow-up tasks.',
                'route' => route('manager.leads.index'),
                'status' => 'Live',
            ],
            [
                'name' => 'User Management',
                'description' => 'Manage owner, manager, and agent access, profile details, and role assignments.',
                'route' => route('admin.users.index'),
                'status' => 'Live',
            ],
            [
                'name' => 'Lead Import',
                'description' => 'Download the CSV template and import lead data in bulk.',
                'route' => route('admin.imports.leads.index'),
                'status' => 'Live',
            ],
            [
                'name' => 'Prospecting Tool',
                'description' => 'Review one prospect card at a time, enrich contact details, and save qualified leads.',
                'route' => route('admin.prospecting.index'),
                'status' => 'Live',
            ],
            [
                'name' => 'Email Templates',
                'description' => 'Edit inquiry confirmation emails, preview content, and test sends.',
                'route' => route('admin.email-templates.index'),
                'status' => 'Live',
            ],
            [
                'name' => 'Analytics',
                'description' => 'Track campaign attribution, conversion velocity, and operational trends.',
                'route' => null,
                'status' => 'Coming Soon',
            ],
            [
                'name' => 'Marketing',
                'description' => 'Manage intake channels, campaign sources, and messaging experiments.',
                'route' => null,
                'status' => 'Coming Soon',
            ],
        ];

        return view('admin.dashboard', compact('metrics', 'modules'));
    }
}
