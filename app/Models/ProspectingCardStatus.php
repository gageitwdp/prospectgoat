<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'account_id',
    'user_id',
    'card_key',
    'skipped',
    'called',
    'left_voicemail',
    'sent_text',
    'created_at',
    'updated_at',
])]
class ProspectingCardStatus extends Model
{
    protected $table = 'prospecting_card_statuses';
}
