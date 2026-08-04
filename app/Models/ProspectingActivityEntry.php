<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'account_id',
    'user_id',
    'activity_date',
    'activity_type',
    'quantity',
    'notes',
])]
class ProspectingActivityEntry extends Model
{
    protected $casts = [
        'activity_date' => 'date',
        'quantity' => 'integer',
    ];
}