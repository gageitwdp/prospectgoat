<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'email',
    'profile_image_path',
    'password',
    'account_id',
    'role',
    'notify_on_new_lead_intake',
    'notify_on_lead_assignment',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    public const ROLE_GLOBAL_ADMIN = 'global_admin';

    public const ROLE_OWNER = 'owner';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_MANAGER = 'manager';

    public const ROLE_AGENT = 'agent';

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'notify_on_new_lead_intake' => 'boolean',
            'notify_on_lead_assignment' => 'boolean',
        ];
    }

    public function assignedLeads(): HasMany
    {
        return $this->hasMany(Lead::class, 'assigned_to');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function isOwner(): bool
    {
        return in_array($this->role, [
            self::ROLE_OWNER,
            self::ROLE_ADMIN,
            self::ROLE_GLOBAL_ADMIN,
        ], true);
    }

    public function isGlobalAdmin(): bool
    {
        return $this->role === self::ROLE_GLOBAL_ADMIN;
    }

    public function isManagerRole(): bool
    {
        return $this->role === self::ROLE_MANAGER;
    }

    public function isAgent(): bool
    {
        return $this->role === self::ROLE_AGENT;
    }

    public function canAccessManagerPortal(): bool
    {
        return in_array($this->role, [
            self::ROLE_GLOBAL_ADMIN,
            self::ROLE_OWNER,
            self::ROLE_ADMIN,
            self::ROLE_MANAGER,
            self::ROLE_AGENT,
        ], true);
    }

    public function isManager(): bool
    {
        return $this->canAccessManagerPortal();
    }
}
