<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name',
    'email',
    'phone',
    'address',
    'lead_type',
    'source',
    'status',
    'assigned_to',
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
}
