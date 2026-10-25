<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissioningItem extends Model
{
    protected $fillable = ['commissioning_id', 'group_name', 'label', 'is_critical', 'reference', 'result', 'note', 'photo_path', 'sort_order'];

    protected function casts(): array
    {
        return ['is_critical' => 'boolean'];
    }

    public function commissioning(): BelongsTo
    {
        return $this->belongsTo(Commissioning::class);
    }
}
