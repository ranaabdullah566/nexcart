<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Page extends Model
{
    use Audited;

    use HasSlug, HasTranslations, SoftDeletes;

    protected $fillable = ['title', 'slug', 'body', 'is_published'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }

    /** HasSlug builds the slug from `name`; for a page that is its title. */
    public function getNameAttribute(): ?string
    {
        return $this->attributes['title'] ?? null;
    }
}
