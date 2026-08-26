<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationRule extends Model
{
    use HasFactory;

    protected $fillable = ['event', 'category_id', 'related_to_group_id', 'group_id', 'channel', 'template', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Si se define, la regla solo aplica a tickets cuyo "Related to" sea este
     * grupo — evita que, p. ej., una regla apuntada a Contabilidad le llegue
     * a tickets relacionados con otro equipo.
     *
     * @return BelongsTo<Group, $this>
     */
    public function relatedToGroup(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'related_to_group_id');
    }

    /**
     * @return BelongsTo<Group, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }
}
