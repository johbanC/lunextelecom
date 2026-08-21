<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketFieldValue extends Model
{
    use HasFactory;

    protected $fillable = ['ticket_id', 'field_definition_id', 'value'];

    /**
     * @return BelongsTo<Ticket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * @return BelongsTo<FieldDefinition, $this>
     */
    public function fieldDefinition(): BelongsTo
    {
        return $this->belongsTo(FieldDefinition::class);
    }

    /**
     * Valor decodificado: array si el campo es multi-valor (checkbox/pick_n), string si no.
     */
    public function decodedValue(): array|string|null
    {
        if ($this->value === null) {
            return null;
        }

        if ($this->fieldDefinition?->isMultiValue()) {
            return json_decode($this->value, true) ?? [];
        }

        return $this->value;
    }
}
