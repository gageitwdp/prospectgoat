<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Services\Billing\GlobalAdminBillingOverviewService;
use Illuminate\View\View;

class GlobalAccountOversightController extends Controller
{
    public function __construct(private readonly GlobalAdminBillingOverviewService $billingOverview) {}

    public function index(): View
    {
        abort_unless(auth()->user()?->isGlobalAdmin(), 403);

        return view('admin.global-account-oversight.index', [
            'globalAccountOverview' => $this->billingOverview->buildOverview(),
            'serviceLevelLabels' => [
                Account::SERVICE_LEVEL_SINGLE_AGENT => 'Single Agent',
                Account::SERVICE_LEVEL_TEAM => 'Team',
                Account::SERVICE_LEVEL_BROKERAGE => 'Brokerage',
            ],
        ]);
    }
}