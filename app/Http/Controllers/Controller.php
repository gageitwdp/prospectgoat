<?php

namespace App\Http\Controllers;

use App\Models\Account;
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

        if (! is_numeric($accountId) || (int) $accountId <= 0) {
            throw new HttpException(503, 'No account is configured for public intake.');
        }

        return (int) $accountId;
    }
}
