<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Agreement extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'type',
        'status',
        'created_by',
        'account_id',
        'form_date',
        'expires_at',
        'owner_name',
        'phone',
        'business_name',
        'address',
        'last_4_bank',
        'training_date',
        'authorization_digits',
        'items',
        'total_amount',
        'signature_path',
        'signed_ip',
        'signed_at',
    ];

    protected $casts = [
        'form_date' => 'date',
        'expires_at' => 'datetime',
        'training_date' => 'date',
        'signed_at' => 'datetime',
        'items' => 'array',
        'total_amount' => 'decimal:2',
    ];

    /**
     * Duraciones de vigencia disponibles al generar o extender un enlace.
     */
    public static function expirationOptions(): array
    {
        return [
            '1' => __(':count day', ['count' => 1]),
            '3' => __(':count days', ['count' => 3]),
            '7' => __(':count days', ['count' => 7]),
            '15' => __(':count days', ['count' => 15]),
            '30' => __(':count days', ['count' => 30]),
            'none' => __('No expiration'),
        ];
    }

    public static function expiresAtFromOption(?string $option): ?\Illuminate\Support\Carbon
    {
        if (empty($option) || $option === 'none') {
            return null;
        }

        return now()->addDays((int) $option);
    }

    /**
     * Catálogo de items por tipo de formulario. Permite agregar nuevos
     * tipos de formulario en el futuro sin cambiar la estructura de datos.
     */
    public static function itemCatalog(string $type): array
    {
        return match ($type) {
            'coam_equipment' => [
                ['key' => 'laptop', 'name' => __('LAPTOP'), 'description' => __('(Installation, Training, Scanner & Cards)'), 'rate' => 329.00],
                ['key' => 'pax_terminal', 'name' => __('PAX TERMINAL'), 'description' => __('(Installation, Training, Scanner & Cards)'), 'rate' => 329.00],
                ['key' => 'in_person_training', 'name' => __('IN PERSON TRAINING ONLY'), 'description' => __('(Includes Scanner & cards)'), 'rate' => 129.00],
                ['key' => 'remote_training', 'name' => __('REMOTE TRAINING ONLY'), 'description' => __('(Includes Scanner & cards) shipped'), 'rate' => 79.00],
                ['key' => 'scanner_only', 'name' => __('SCANNER ONLY'), 'description' => '', 'rate' => 30.00],
                ['key' => 'coam_cards', 'name' => __('COAM CARDS'), 'description' => '', 'rate' => 0.00],
            ],
            default => [],
        };
    }

    public static function typeLabel(string $type): string
    {
        return match ($type) {
            'coam_equipment' => __('GA COAM Equipment Inquiry'),
            default => $type,
        };
    }

    protected static function booted(): void
    {
        static::creating(function (Agreement $agreement) {
            if (empty($agreement->uuid)) {
                $agreement->uuid = (string) Str::uuid();
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()));
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'pending')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now());
    }

    public function scopeSigned($query)
    {
        return $query->where('status', 'signed');
    }

    public function isSigned(): bool
    {
        return $this->status === 'signed';
    }

    public function isExpired(): bool
    {
        return ! $this->isSigned() && $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function publicUrl(): string
    {
        return route('public.agreements.show', $this->uuid);
    }

    protected function accountId(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => strtoupper($value),
        );
    }
}
