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
        $lead->activities()->create($request->validated());

        return back()->with('status', 'Lead activity added.');
    }
}
