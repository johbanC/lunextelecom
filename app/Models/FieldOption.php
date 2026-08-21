<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FieldOption extends Model
{
    use HasFactory;

    protected $fillable = ['field_definition_id', 'value', 'sort_order'];

    /**
     * @return BelongsTo<FieldDefinition, $this>
     */
    public function fieldDefinition(): BelongsTo
    {
        return $this->belongsTo(FieldDefinition::class);
    }
}
