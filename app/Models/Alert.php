<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alert extends Model
{
    public const SEVERITY_ORDER = ['kritis' => 0, 'tinggi' => 1, 'sedang' => 2, 'info' => 3];

    public const SEVERITY_LABELS = ['kritis' => 'Kritis', 'tinggi' => 'Tinggi', 'sedang' => 'Sedang', 'info' => 'Info'];

    public const SEVERITY_BADGES = ['kritis' => 'danger', 'tinggi' => 'warning', 'sedang' => 'primary', 'info' => 'secondary'];

    public const SEVERITY_COLORS = ['kritis' => '#dc2626', 'tinggi' => '#f59e0b', 'sedang' => '#2563eb', 'info' => '#94a3b8'];

    public const TYPE_LABELS = [
        'unit_hold' => 'Unit ditahan (hold)',
        'commissioning_expired' => 'Commissioning kedaluwarsa',
        'commissioning_expiring' => 'Commissioning segera berakhir',
        'not_commissioned' => 'Belum commissioning',
        'doc_expired' => 'Dokumen kedaluwarsa',
        'doc_expiring' => 'Dokumen segera berakhir',
        'finding_critical' => 'Temuan kritis terbuka',
        'finding_overdue' => 'Temuan lewat tenggat',
        'approval_overdue' => 'Persetujuan terlambat',
        'idle_recommission' => 'Idle: perlu recommissioning',
    ];

    protected $fillable = [
        'unit_id', 'dedupe_key', 'type', 'severity', 'title', 'message', 'due_date', 'first_seen_at', 'last_seen_at',
        'acknowledged_by_name', 'acknowledged_at', 'resolved_at',
    ];

    protected function casts(): array
    {
        return ['due_date' => 'date', 'first_seen_at' => 'datetime', 'last_seen_at' => 'datetime', 'acknowledged_at' => 'datetime', 'resolved_at' => 'datetime'];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('resolved_at');
    }

    public function typeLabel(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }
}
