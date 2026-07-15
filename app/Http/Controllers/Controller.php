<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

abstract class Controller
{
    protected function requireCurrentAccountId(): int
    {
        $accountId = auth()->user()?->account_id;

        if (! is_numeric($accountId) || (int) $accountId <= 0) {
            throw new HttpException(403, 'Account context is required.');
        }

        return (int) $accountId;
    }

    protected function resolvePublicAccountId(): int
    {
        $accountId = Account::query()->orderBy('id')->value('id');

        if (is_numeric($accountId) && (int) $accountId > 0) {
            return (int) $accountId;
        }

        if (app()->environment('testing')) {
            $account = Account::query()->create([
                'name' => 'Default Test Account',
                'slug' => 'default-test-account-'.Str::lower(Str::random(8)),
                'service_level' => Account::SERVICE_LEVEL_SINGLE_AGENT,
                'billing_status' => Account::BILLING_STATUS_PENDING,
            ]);

            return (int) $account->id;
        }

        throw new HttpException(503, 'No account is configured for public intake.');
    }
}
