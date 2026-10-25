<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    public const STATUS_LABELS = [
        'commissioned' => 'Laik operasi',
        'expiring' => 'Segera berakhir',
        'expired' => 'Kedaluwarsa',
        'hold' => 'Ditahan (hold)',
        'in_process' => 'Dalam proses',
        'not_commissioned' => 'Belum commissioning',
    ];

    public const STATUS_COLORS = [
        'commissioned' => '#16a34a',
        'expiring' => '#f59e0b',
        'expired' => '#dc2626',
        'hold' => '#7f1d1d',
        'in_process' => '#2563eb',
        'not_commissioned' => '#94a3b8',
    ];

    public const STATUS_BADGES = [
        'commissioned' => 'success',
        'expiring' => 'warning',
        'expired' => 'danger',
        'hold' => 'dark',
        'in_process' => 'primary',
        'not_commissioned' => 'secondary',
    ];

    protected $fillable = [
        'code', 'name', 'category', 'brand', 'model', 'serial_number', 'year', 'plate', 'owner', 'area', 'is_active',
        'commissioned_until', 'last_commissioned_at', 'last_commissioning_id', 'hold_since', 'hold_reason', 'idle_since', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'commissioned_until' => 'date',
            'last_commissioned_at' => 'datetime',
            'hold_since' => 'datetime',
            'idle_since' => 'date',
        ];
    }

    public static function normalizeCode(string $code): string
    {
        return strtoupper(trim($code));
    }

    public function commissionings(): HasMany
    {
        return $this->hasMany(Commissioning::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(UnitDocument::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    public function findings(): HasMany
    {
        return $this->hasMany(Finding::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Menambah atribut has_process: ada commissioning yang masih berjalan (draf / diajukan / perlu perbaikan). */
    public function scopeWithProcess(Builder $query): Builder
    {
        return $query->withExists(['commissionings as has_process' => fn ($q) => $q->whereIn('status', Commissioning::ACTIVE_STATUSES)]);
    }

    public function hasActiveProcess(): bool
    {
        return array_key_exists('has_process', $this->attributes)
            ? (bool) $this->attributes['has_process']
            : $this->commissionings()->whereIn('status', Commissioning::ACTIVE_STATUSES)->exists();
    }

    /** Sisa hari masa berlaku commissioning (negatif bila sudah lewat); null bila belum pernah laik. */
    public function daysLeft(?CarbonInterface $today = null): ?int
    {
        if (! $this->commissioned_until) {
            return null;
        }
        $today ??= today();

        return (int) $today->diffInDays($this->commissioned_until, false);
    }

    /** Status operasional: dihitung dari data (bukan disimpan) karena masa berlaku berubah seiring waktu. */
    public function operationalStatus(?CarbonInterface $today = null): string
    {
        if ($this->hold_since) {
            return 'hold';
        }

        $left = $this->daysLeft($today);
        if ($left !== null) {
            if ($left < 0) {
                return 'expired';
            }

            return $left <= (int) config('commissioning.expiring_days') ? 'expiring' : 'commissioned';
        }

        return $this->hasActiveProcess() ? 'in_process' : 'not_commissioned';
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->operationalStatus()];
    }

    public function isFit(): bool
    {
        return in_array($this->operationalStatus(), ['commissioned', 'expiring'], true);
    }

    public function categoryName(): string
    {
        return config('commissioning.categories.'.$this->category, $this->category);
    }
}
