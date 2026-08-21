<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Ticket extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'ticket_number',
        'ticket_type_id',
        'category_id',
        'issue_id',
        'header',
        'status',
        'priority',
        'related_to_group_id',
        'assignee_id',
        'created_by',
        'sla_status_since',
        'sla_warning_notified_at',
        'sla_breached_notified_at',
        'resolved_at',
        'closed_at',
    ];

    protected $casts = [
        'header' => 'array',
        'sla_status_since' => 'datetime',
        'sla_warning_notified_at' => 'datetime',
        'sla_breached_notified_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Ticket $ticket) {
            if (empty($ticket->ticket_number)) {
                $ticket->ticket_number = static::generateTicketNumber();
            }
        });
    }

    protected static function generateTicketNumber(): string
    {
        return 'LX-'.now()->format('ymd').'-'.Str::upper(Str::random(5));
    }

    /**
     * Nombre a mostrar en listados a partir del encabezado (Retailer u Owner/Full name).
     */
    public function headerTitle(): ?string
    {
        return $this->header['owner']
            ?? $this->header['full_name']
            ?? $this->header['retailer_code']
            ?? null;
    }

    /**
     * Umbral del semáforo de tiempo: green | yellow | red | done.
     * Una vez resuelto/cerrado ya no hay nada pendiente, así que el ticket
     * deja de acumular alerta (si no, se vería "rojo" para siempre).
     */
    public function slaStatus(): string
    {
        if (in_array($this->status, [self::STATUS_RESOLVED, self::STATUS_CLOSED], true)) {
            return 'done';
        }

        $days = $this->sla_status_since->diffInDays(now());
        $category = $this->category;

        if ($days >= $category->sla_red_days) {
            return 'red';
        }

        if ($days >= $category->sla_yellow_days) {
            return 'yellow';
        }

        return 'green';
    }

    /**
     * @return BelongsTo<TicketType, $this>
     */
    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<Issue, $this>
     */
    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }

    /**
     * @return BelongsTo<Group, $this>
     */
    public function relatedToGroup(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'related_to_group_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<TicketExtraCustomer, $this>
     */
    public function extraCustomers(): HasMany
    {
        return $this->hasMany(TicketExtraCustomer::class);
    }

    /**
     * @return HasMany<TicketFieldValue, $this>
     */
    public function fieldValues(): HasMany
    {
        return $this->hasMany(TicketFieldValue::class);
    }

    /**
     * @return HasMany<TicketEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(TicketEvent::class)->orderBy('created_at');
    }

    /**
     * @return HasMany<TicketComment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class)->orderBy('created_at');
    }

    /**
     * @return HasMany<Attachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }
}
