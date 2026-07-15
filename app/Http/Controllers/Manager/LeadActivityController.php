<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeadActivityRequest;
use App\Models\Lead;
use Illuminate\Http\RedirectResponse;

class LeadActivityController extends Controller
{
    public function store(StoreLeadActivityRequest $request, Lead $lead): RedirectResponse
    {
        abort_unless($lead->account_id === $this->requireCurrentAccountId(), 404);

        $lead->activities()->create([
            ...$request->validated(),
            'account_id' => $lead->account_id,
        ]);

        return back()->with('status', 'Lead activity added.');
    }
}
