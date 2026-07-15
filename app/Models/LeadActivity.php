<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['account_id', 'lead_id', 'type', 'description'])]
class LeadActivity extends Model
{
    const UPDATED_AT = null;

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
