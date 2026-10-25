<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Commissioning extends Model
{
    public const ACTIVE_STATUSES = ['draft', 'submitted', 'rejected'];

    public const STATUS_LABELS = [
        'draft' => 'Draf',
        'submitted' => 'Diajukan',
        'rejected' => 'Perlu perbaikan',
        'approved' => 'Disetujui',
        'cancelled' => 'Dibatalkan',
    ];

    public const STATUS_BADGES = [
        'draft' => 'secondary',
        'submitted' => 'primary',
        'rejected' => 'warning',
        'approved' => 'success',
        'cancelled' => 'dark',
    ];

    public const KIND_LABELS = [
        'commissioning' => 'Commissioning',
        'recommissioning' => 'Recommissioning',
    ];

    public const RESULT_LABELS = [
        'laik' => 'LAIK OPERASI',
        'laik_catatan' => 'LAIK DENGAN CATATAN',
    ];

    protected $fillable = [
        'number', 'unit_id', 'unit_code', 'unit_name', 'category', 'kind', 'reason', 'reason_note', 'area', 'planned_date',
        'meter_reading', 'meter_unit', 'status', 'result', 'valid_until',
        'requested_by_id', 'requested_by_name', 'requested_by_nik', 'submitted_at',
        'safety_by_name', 'safety_by_id', 'safety_at', 'safety_notes',
        'workshop_by_name', 'workshop_by_id', 'workshop_at', 'workshop_notes',
        'decided_by_name', 'decided_by_id', 'decided_at', 'decision_notes', 'returned_reason', 'cancelled_reason',
    ];

    protected function casts(): array
    {
        return [
            'planned_date' => 'date',
            'valid_until' => 'date',
            'submitted_at' => 'datetime',
            'safety_at' => 'datetime',
            'workshop_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'number';
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CommissioningItem::class)->orderBy('sort_order')->orderBy('id');
    }

    public function findings(): HasMany
    {
        return $this->hasMany(Finding::class);
    }

    public static function nextNumber(): string
    {
        $prefix = 'CMS-'.now()->format('ym').'-';
        $last = static::query()->where('number', 'like', $prefix.'%')->orderByDesc('number')->value('number');
        $seq = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'rejected'], true);
    }

    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES, true);
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function kindLabel(): string
    {
        return self::KIND_LABELS[$this->kind] ?? $this->kind;
    }

    public function reasonLabel(): ?string
    {
        return $this->reason ? config('commissioning.reasons.'.$this->reason, $this->reason) : null;
    }

    public function bothVerified(): bool
    {
        return $this->safety_at !== null && $this->workshop_at !== null;
    }

    /** Tahap yang sedang ditunggu, untuk ditampilkan di daftar dan dashboard. */
    public function stage(): string
    {
        return match (true) {
            $this->status === 'draft' => 'Diisi pemeriksa',
            $this->status === 'rejected' => 'Perbaikan oleh pemeriksa',
            $this->status === 'submitted' && ! $this->safety_at && ! $this->workshop_at => 'Menunggu verifikasi Safety dan Workshop',
            $this->status === 'submitted' && ! $this->safety_at => 'Menunggu verifikasi Safety',
            $this->status === 'submitted' && ! $this->workshop_at => 'Menunggu verifikasi Workshop',
            $this->status === 'submitted' => 'Menunggu keputusan penyetuju',
            $this->status === 'approved' => 'Selesai',
            default => 'Dibatalkan',
        };
    }
}
