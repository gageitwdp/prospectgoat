<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'account_id',
    'name',
    'owner_2_full_name',
    'email',
    'owner_2_email',
    'phone',
    'owner_2_phone',
    'address',
    'prospecting_notes',
    'lead_type',
    'source',
    'status',
    'assigned_to',
    'created_by',
    'working_with_agent',
    'move_timeline',
    'move_if_not_found',
    'price_range',
    'mortgage_preapproval_status',
    'need_to_sell_current_home',
    'agent_relationship',
    'purchase_reason',
    'target_areas',
    'min_bedrooms',
    'min_bathrooms',
    'preferred_contact_method',
    'seller_timeline',
    'seller_motivation',
    'seller_estimated_home_value',
    'seller_mortgage_status',
    'seller_needs_to_buy_another_home_after_selling',
    'seller_property_condition',
    'seller_major_upgrades',
    'seller_agent_commitment',
    'seller_occupancy_status',
    'seller_valuation_delivery_method',
])]
class Lead extends Model
{
    use SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (Lead $lead): void {
            if ($lead->account_id === null) {
                $lead->account_id = (int) (auth()->user()?->account_id
                    ?? Account::query()->orderBy('id')->value('id')) ?: null;
            }

            if ($lead->created_by === null) {
                $lead->created_by = (int) (auth()->id()
                    ?? User::query()
                        ->where('account_id', $lead->account_id)
                        ->whereIn('role', ['owner', 'admin', 'manager', 'agent'])
                        ->orderByRaw("CASE role WHEN 'owner' THEN 1 WHEN 'admin' THEN 2 WHEN 'manager' THEN 3 WHEN 'agent' THEN 4 ELSE 5 END")
                        ->orderBy('id')
                        ->value('id')) ?: null;
            }
        });
    }

    public function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
            'working_with_agent' => 'boolean',
            'min_bedrooms' => 'integer',
            'min_bathrooms' => 'decimal:1',
        ];
    }

    public function assignedManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function eventRegistrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isGlobalAdmin()) {
            return $query->whereRaw('1 = 0');
        }

        $accountId = (int) ($user->account_id ?? 0);
        if ($accountId <= 0) {
            return $query->whereRaw('1 = 0');
        }

        $query->where('account_id', $accountId);

        if (in_array($user->account?->service_level, [
            Account::SERVICE_LEVEL_TEAM,
            Account::SERVICE_LEVEL_BROKERAGE,
        ], true)) {
            return $query;
        }

        return $query->where('created_by', $user->id);
    }
}
