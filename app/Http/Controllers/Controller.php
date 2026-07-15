<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

abstract class Controller
{
    protected function currentUserIsGlobalAdmin(): bool
    {
        return (bool) auth()->user()?->isGlobalAdmin();
    }

    protected function requireCurrentAccountId(): int
    {
        $user = auth()->user();
        $accountId = $user?->account_id;

        if (is_numeric($accountId) && (int) $accountId > 0) {
            return (int) $accountId;
        }

        if ($user) {
            $fallbackAccountId = Account::query()->orderBy('id')->value('id');

            if (! is_numeric($fallbackAccountId) || (int) $fallbackAccountId <= 0) {
                $slug = 'default-account-'.Str::lower(Str::random(8));

                $account = Account::query()->create([
                    'name' => 'Default Account',
                    'slug' => $slug,
                    'service_level' => Account::SERVICE_LEVEL_SINGLE_AGENT,
                    'billing_status' => Account::BILLING_STATUS_PENDING,
                ]);

                $fallbackAccountId = $account->id;
            }

            $user->forceFill(['account_id' => (int) $fallbackAccountId])->save();

            return (int) $fallbackAccountId;
        }

        throw new HttpException(403, 'Account context is required.');
    }

    protected function inCurrentAccountScope(?int $resourceAccountId, bool $allowNull = false): bool
    {
        if ($this->currentUserIsGlobalAdmin()) {
            return true;
        }

        if ($allowNull && $resourceAccountId === null) {
            return true;
        }

        return $resourceAccountId !== null
            && $resourceAccountId === $this->requireCurrentAccountId();
    }

    protected function applyAccountScope($query, string $column = 'account_id', bool $includeNull = false)
    {
        if ($this->currentUserIsGlobalAdmin()) {
            return $query;
        }

        $accountId = $this->requireCurrentAccountId();

        if ($includeNull) {
            return $query->where(function ($innerQuery) use ($column, $accountId) {
                $innerQuery->where($column, $accountId)
                    ->orWhereNull($column);
            });
        }

        return $query->where($column, $accountId);
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
