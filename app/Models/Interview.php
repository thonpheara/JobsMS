<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Interview extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'application_id',
        'scheduled_at',
        'meeting_type',
        'location_or_link',
        'notes',
        'status',
        'confirmed_by_student',
        'confirmed_at',
        'reschedule_reason',
    ];

    protected $appends = [
        'type',
        'location',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at'         => 'datetime',
            'confirmed_at'         => 'datetime',
            'confirmed_by_student' => 'boolean',
        ];
    }

    public function getTypeAttribute(): ?string
    {
        return $this->meeting_type;
    }

    public function getLocationAttribute(): ?string
    {
        return $this->location_or_link;
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function isConfirmed(): bool
    {
        return $this->confirmed_by_student || $this->status === 'confirmed';
    }

    public function isRescheduleRequested(): bool
    {
        return $this->status === 'reschedule_requested';
    }

    public function isOnline(): bool
    {
        return $this->meeting_type === 'online' || filter_var($this->location_or_link, FILTER_VALIDATE_URL) !== false;
    }
}

