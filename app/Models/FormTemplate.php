<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormTemplate extends Model
{
    use HasFactory;

    public const MODE_ON_DEMAND = 'on_demand';

    public const MODE_STANDALONE = 'standalone';

    protected $fillable = [
        'name', 'slug', 'instructions', 'requires_signature',
        'mode', 'notify_group_id', 'is_active', 'created_by',
    ];

    protected $casts = [
        'requires_signature' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function isStandalone(): bool
    {
        return $this->mode === self::MODE_STANDALONE;
    }

    /**
     * @return HasMany<FormField, $this>
     */
    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<FormSubmission, $this>
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class);
    }

    /**
     * @return BelongsTo<Group, $this>
     */
    public function notifyGroup(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'notify_group_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
