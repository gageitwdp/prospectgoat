<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeadActivityRequest;
use App\Models\Lead;
use Illuminate\Http\RedirectResponse;

class LeadActivityController extends Controller
{
    public function __construct()
    {
        abort_if($this->currentUserIsGlobalAdmin(), 403);
    }

    public function store(StoreLeadActivityRequest $request, Lead $lead): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user, 403);

        $accountId = $this->requireCurrentAccountId();
        abort_unless(
            Lead::query()
                ->withTrashed()
                ->visibleTo($user)
                ->whereKey($lead->id)
                ->exists(),
            404,
        );

        $lead->activities()->create([
            ...$request->validated(),
            'account_id' => $lead->account_id ?? $accountId,
        ]);

        return back()->with('status', 'Lead activity added.');
    }
}
