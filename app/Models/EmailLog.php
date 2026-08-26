<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailLog extends Model
{
    protected $fillable = [
        'tracking_token',
        'to_email',
        'to_name',
        'user_id',
        'ticket_id',
        'agreement_id',
        'event',
        'purpose',
        'subject',
        'body_html',
        'status',
        'error_message',
        'sent_at',
        'opened_at',
        'open_count',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'opened_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }

    public function markOpened(): void
    {
        $this->increment('open_count');

        if (! $this->opened_at) {
            $this->update(['opened_at' => now()]);
        }
    }
}
