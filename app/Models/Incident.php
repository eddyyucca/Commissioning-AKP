<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Incident extends Model
{
    protected $fillable = [
        'unit_id', 'occurred_at', 'kind', 'severity', 'description', 'requires_recommissioning',
        'reported_by_name', 'reported_by_id', 'status', 'resolved_by_commissioning_id', 'resolved_at',
    ];

    protected function casts(): array
    {
        return ['occurred_at' => 'datetime', 'resolved_at' => 'datetime', 'requires_recommissioning' => 'boolean'];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function kindLabel(): string
    {
        return config('commissioning.incident_kinds.'.$this->kind.'.0', $this->kind);
    }

    public function severityLabel(): string
    {
        return config('commissioning.incident_severities.'.$this->severity, $this->severity);
    }
}
