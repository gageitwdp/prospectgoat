<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'slug',
    'service_level',
    'billing_status',
    'stripe_customer_id',
    'stripe_subscription_id',
    'trial_ends_at',
    'last_billing_sync_at',
    'last_billing_event_type',
    'last_billing_event_id',
])]
class Account extends Model
{
    use HasFactory;

    public const SERVICE_LEVEL_SINGLE_AGENT = 'single_agent';

    public const SERVICE_LEVEL_TEAM = 'team';

    public const SERVICE_LEVEL_BROKERAGE = 'brokerage';

    public const BILLING_STATUS_PENDING = 'pending';

    public const BILLING_STATUS_ACTIVE = 'active';

    public const BILLING_STATUS_PAST_DUE = 'past_due';

    public const BILLING_STATUS_CANCELED = 'canceled';

    public const BILLING_STATUS_TRIALING = 'trialing';

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'last_billing_sync_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function hasActiveBilling(): bool
    {
        return in_array($this->billing_status, [
            self::BILLING_STATUS_ACTIVE,
            self::BILLING_STATUS_TRIALING,
        ], true);
    }

    public function requiresBillingSetup(): bool
    {
        return ! $this->hasActiveBilling();
    }
}
