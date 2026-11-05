<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasSlug, HasTranslations;

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
