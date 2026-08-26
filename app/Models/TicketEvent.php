<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketEvent extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = ['ticket_id', 'user_id', 'type', 'payload'];

    protected $casts = [
        'payload' => 'array',
    ];

    /**
     * Descripción legible del evento, incluyendo el valor anterior y el nuevo
     * cuando el payload los trae (status_changed, assigned, reassigned).
     */
    public function describe(): string
    {
        $payload = $this->payload ?? [];

        return match ($this->type) {
            'created' => __('created the ticket'),
            'status_changed' => __('changed status from :from to :to', [
                'from' => $this->statusLabel($payload['from'] ?? null),
                'to' => $this->statusLabel($payload['to'] ?? null),
            ]),
            'assigned' => __('assigned the ticket to :to', ['to' => $this->userLabel($payload['to'] ?? null)]),
            'reassigned' => $this->describeReassigned($payload),
            'comment_added' => __('added a comment'),
            'attachment_added' => trans_choice('{1} attached 1 file|[2,*] attached :count files', $payload['count'] ?? 1, ['count' => $payload['count'] ?? 1]),
            'fields_updated' => trans_choice(
                '{1} updated 1 field|[2,*] updated :count fields',
                count($payload['fields'] ?? []),
                ['count' => count($payload['fields'] ?? [])]
            ),
            default => str_replace('_', ' ', $this->type),
        };
    }

    /**
     * Detalle campo-por-campo (label, valor anterior, valor nuevo) para
     * eventos fields_updated — la vista lo usa para mostrar exactamente
     * qué cambió, no solo qué campos.
     *
     * @return array<int, array{label: string, from: ?string, to: ?string}>
     */
    public function fieldChanges(): array
    {
        return $this->type === 'fields_updated' ? ($this->payload['fields'] ?? []) : [];
    }

    protected function describeReassigned(array $payload): string
    {
        if (array_key_exists('group_from', $payload) || array_key_exists('group_to', $payload)) {
            return __('changed related group from :from to :to', [
                'from' => $this->groupLabel($payload['group_from'] ?? null),
                'to' => $this->groupLabel($payload['group_to'] ?? null),
            ]);
        }

        return __('reassigned the ticket from :from to :to', [
            'from' => $this->userLabel($payload['from'] ?? null),
            'to' => $this->userLabel($payload['to'] ?? null),
        ]);
    }

    protected function statusLabel(?string $status): string
    {
        return $status ? Ticket::statusLabel($status) : __('—');
    }

    protected function userLabel(?int $userId): string
    {
        return $userId ? (User::find($userId)?->name ?? __('Unknown user')) : __('Unassigned');
    }

    protected function groupLabel(?int $groupId): string
    {
        return $groupId ? (Group::find($groupId)?->name ?? __('Unknown group')) : __('Unassigned group');
    }

    /**
     * @return BelongsTo<Ticket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
