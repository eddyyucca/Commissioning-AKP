<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChecklistTemplateItem extends Model
{
    protected $fillable = ['scope', 'categories', 'reasons', 'group_name', 'label', 'is_critical', 'reference', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['is_critical' => 'boolean', 'is_active' => 'boolean'];
    }

    public function categoryList(): array
    {
        return $this->categories === 'all' ? [] : array_values(array_filter(array_map('trim', explode(',', $this->categories))));
    }

    public function reasonList(): array
    {
        return $this->reasons ? array_values(array_filter(array_map('trim', explode(',', $this->reasons)))) : [];
    }

    /** Apakah item ini berlaku untuk jenis, kategori unit, dan alasan tertentu? */
    public function appliesTo(string $kind, string $category, ?string $reason): bool
    {
        if (! $this->is_active) {
            return false;
        }
        if ($this->scope !== 'both' && $this->scope !== $kind) {
            return false;
        }
        $cats = $this->categoryList();
        if ($cats && ! in_array($category, $cats, true)) {
            return false;
        }
        $reasons = $this->reasonList();

        return ! $reasons || ($reason !== null && in_array($reason, $reasons, true));
    }
}
