<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormSubmission extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'uuid', 'form_template_id', 'status', 'expires_at', 'signature_path',
        'signed_ip', 'submitted_at', 'reference_note',
        'created_by', 'managed_by', 'managed_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'submitted_at' => 'datetime',
        'managed_at' => 'datetime',
    ];

    public function isSubmitted(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    public function isExpired(): bool
    {
        return ! $this->isSubmitted() && $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isManaged(): bool
    {
        return $this->managed_at !== null;
    }

    public function publicUrl(): string
    {
        return $this->template->isStandalone()
            ? route('public.forms.standalone.show', $this->template->slug)
            : route('public.forms.show', $this->uuid);
    }

    /**
     * Reemplaza placeholders {{clave}} en el texto de la plantilla (ej.
     * "Hello, {{full_name}}...") con los valores ya capturados de este
     * envío — para las plantillas de tipo "narrative" (texto + firma, sin
     * cajas de campo sueltas).
     */
    public function interpolatedInstructions(): string
    {
        $text = $this->template->instructions ?? '';

        $values = $this->values->mapWithKeys(fn (FormSubmissionValue $value) => [
            $value->field->key => $value->value,
        ]);

        return preg_replace_callback('/\{\{\s*([a-z0-9_]+)\s*\}\}/i', function (array $matches) use ($values) {
            return $values[$matches[1]] ?? $matches[0];
        }, $text);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()));
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', self::STATUS_SUBMITTED);
    }

    public function scopeToManage($query)
    {
        return $query->where('status', self::STATUS_SUBMITTED)->whereNull('managed_at');
    }

    /**
     * @return BelongsTo<FormTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(FormTemplate::class, 'form_template_id');
    }

    /**
     * @return HasMany<FormSubmissionValue, $this>
     */
    public function values(): HasMany
    {
        return $this->hasMany(FormSubmissionValue::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'managed_by');
    }
}
