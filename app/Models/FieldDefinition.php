<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class FieldDefinition extends Model
{
    use HasFactory;

    public const TYPE_TEXT = 'text';

    public const TYPE_TEXTAREA = 'textarea';

    public const TYPE_SELECT = 'select';

    public const TYPE_CHECKBOX = 'checkbox';

    public const TYPE_RADIO = 'radio';

    public const TYPE_PICK_N = 'pick_n';

    public const TYPE_DATE = 'date';

    public const TYPE_FILE = 'file';

    // Tipos que aceptan más de un valor seleccionado por ticket.
    public const MULTI_VALUE_TYPES = [self::TYPE_CHECKBOX, self::TYPE_PICK_N];

    protected $fillable = [
        'issue_id',
        'label',
        'key',
        'field_type',
        'is_required',
        'help_text',
        'pick_count',
        'sort_order',
    ];

    protected $casts = [
        'is_required' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (FieldDefinition $field) {
            if (empty($field->key)) {
                $field->key = Str::slug($field->label, '_');
            }
        });
    }

    public function isMultiValue(): bool
    {
        return in_array($this->field_type, self::MULTI_VALUE_TYPES, true);
    }

    /**
     * @return BelongsTo<Issue, $this>
     */
    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }

    /**
     * @return HasMany<FieldOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(FieldOption::class)->orderBy('sort_order');
    }
}
