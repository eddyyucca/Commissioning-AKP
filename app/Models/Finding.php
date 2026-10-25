<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Finding extends Model
{
    public const SEVERITY_LABELS = ['critical' => 'Kritis', 'major' => 'Mayor', 'minor' => 'Minor'];

    public const SEVERITY_BADGES = ['critical' => 'danger', 'major' => 'warning', 'minor' => 'secondary'];

    public const STATUS_LABELS = ['open' => 'Terbuka', 'in_progress' => 'Dikerjakan', 'closed' => 'Selesai'];

    protected $fillable = [
        'commissioning_id', 'item_id', 'unit_id', 'title', 'severity', 'description', 'action_plan', 'pic_name', 'due_date',
        'status', 'closed_at', 'closed_by_name', 'closed_note',
    ];

    protected function casts(): array
    {
        return ['due_date' => 'date', 'closed_at' => 'datetime'];
    }

    public function commissioning(): BelongsTo
    {
        return $this->belongsTo(Commissioning::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', '!=', 'closed');
    }

    public function isOpen(): bool
    {
        return $this->status !== 'closed';
    }

    public function isOverdue(): bool
    {
        return $this->isOpen() && $this->due_date !== null && $this->due_date->lt(today());
    }
}
