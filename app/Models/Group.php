<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'applies_to', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * @return BelongsToMany<User, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_user')
            ->withPivot('role_in_group')
            ->withTimestamps();
    }

    /**
     * @return HasMany<Ticket, $this>
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'related_to_group_id');
    }

    public function appliesTo(string $ticketTypeCode): bool
    {
        return $this->applies_to === 'both' || $this->applies_to === $ticketTypeCode;
    }
}
