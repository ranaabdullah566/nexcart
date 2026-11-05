<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

/**
 * "Unique" that knows about the Recycle Bin: if the value is held by a deleted record it says so,
 * instead of the confusing "already taken" (the database still enforces uniqueness for deleted rows).
 */
class UniqueActive implements ValidationRule
{
    public function __construct(private string $table, private string $column, private mixed $ignoreId = null, private bool $caseInsensitive = false) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $q = DB::table($this->table);
        $this->caseInsensitive ? $q->whereRaw("lower({$this->column}) = ?", [mb_strtolower((string) $value)]) : $q->where($this->column, $value);
        $row = $q->when($this->ignoreId, fn ($q) => $q->where('id', '!=', $this->ignoreId))->first(['id', 'deleted_at']);

        if (! $row) {
            return;
        }
        $fail($row->deleted_at ? __('This is already used by an item in the Recycle Bin. Restore it, or delete it permanently first.') : __('validation.unique'));
    }
}
