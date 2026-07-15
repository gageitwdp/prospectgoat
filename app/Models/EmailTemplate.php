<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'account_id',
    'key',
    'name',
    'subject',
    'body',
    'is_active',
])]
class EmailTemplate extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public static function resolveForInquiryType(string $inquiryType): ?self
    {
        $key = static::keyForInquiryType($inquiryType);

        return static::resolveForKey($key);
    }

    public static function resolveForKey(string $key): ?self
    {
        $template = static::query()
            ->where('key', $key)
            ->where('is_active', true)
            ->first();

        if ($template) {
            return $template;
        }

        return static::query()
            ->where('key', 'new_lead_default')
            ->where('is_active', true)
            ->first();
    }

    public static function keyForInquiryType(string $inquiryType): string
    {
        return match ($inquiryType) {
            'buyer' => 'new_lead_buyer',
            'seller' => 'new_lead_seller',
            'home_value' => 'new_lead_home_value',
            default => 'new_lead_generic_inquiry',
        };
    }

    public function renderSubject(array $data = []): string
    {
        return static::replacePlaceholders($this->subject, $data);
    }

    public function renderBody(array $data = []): string
    {
        return static::replacePlaceholders($this->body, $data);
    }

    protected static function replacePlaceholders(?string $value, array $data = []): string
    {
        $replacements = collect($data)
            ->mapWithKeys(fn ($item, string $key) => ['{{'.$key.'}}' => (string) $item])
            ->all();

        return strtr((string) $value, $replacements);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}