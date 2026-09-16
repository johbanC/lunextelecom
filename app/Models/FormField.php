<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class FormField extends Model
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

    public const MULTI_VALUE_TYPES = [self::TYPE_CHECKBOX, self::TYPE_PICK_N];

    protected $fillable = [
        'form_template_id', 'label', 'key', 'field_type',
        'is_required', 'editable_by_recipient', 'help_text', 'pick_count', 'sort_order',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'editable_by_recipient' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (FormField $field) {
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
     * @return BelongsTo<FormTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(FormTemplate::class, 'form_template_id');
    }

    /**
     * @return HasMany<FormFieldOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(FormFieldOption::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<FormSubmissionValue, $this>
     */
    public function submissionValues(): HasMany
    {
        return $this->hasMany(FormSubmissionValue::class);
    }
}
