<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnitDocument extends Model
{
    protected $fillable = ['unit_id', 'type', 'number', 'issued_at', 'expires_at', 'notes', 'created_by_name'];

    protected function casts(): array
    {
        return ['issued_at' => 'date', 'expires_at' => 'date'];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function typeLabel(): string
    {
        return config('commissioning.document_types.'.$this->type, $this->type);
    }

    public function daysLeft(): ?int
    {
        return $this->expires_at ? (int) today()->diffInDays($this->expires_at, false) : null;
    }

    /** valid | expiring | expired | none (tanpa tanggal kedaluwarsa) */
    public function state(): string
    {
        $left = $this->daysLeft();
        if ($left === null) {
            return 'none';
        }
        if ($left < 0) {
            return 'expired';
        }

        return $left <= (int) config('commissioning.doc_expiring_days') ? 'expiring' : 'valid';
    }
}
