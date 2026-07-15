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

    public static function resolveForInquiryType(string $inquiryType, ?int $accountId = null): ?self
    {
        $key = static::keyForInquiryType($inquiryType);

        return static::resolveForKey($key, $accountId);
    }

    public static function resolveForKey(string $key, ?int $accountId = null): ?self
    {
        $templateQuery = static::query()
            ->where('key', $key)
            ->where('is_active', true);

        if ($accountId !== null) {
            $templateQuery->where(function ($query) use ($accountId): void {
                $query
                    ->where('account_id', $accountId)
                    ->orWhereNull('account_id');
            })->orderByRaw('CASE WHEN account_id = ? THEN 0 ELSE 1 END', [$accountId]);
        }

        $template = $templateQuery->first();

        if ($template) {
            return $template;
        }

        $fallbackQuery = static::query()
            ->where('key', 'new_lead_default')
            ->where('is_active', true);

        if ($accountId !== null) {
            $fallbackQuery->where(function ($query) use ($accountId): void {
                $query
                    ->where('account_id', $accountId)
                    ->orWhereNull('account_id');
            })->orderByRaw('CASE WHEN account_id = ? THEN 0 ELSE 1 END', [$accountId]);
        }

        return $fallbackQuery->first();
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