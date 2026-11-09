<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/** "Here is my prescription — call me": a customer sends a photo without building a cart; the pharmacy calls back. */
class PrescriptionRequest extends Model implements HasMedia
{
    use Audited, InteractsWithMedia, SoftDeletes;

    public const STATUSES = ['new', 'contacted', 'closed'];

    protected $fillable = ['user_id', 'name', 'phone', 'email', 'city', 'notes', 'status', 'handled_by', 'handled_at'];

    protected function casts(): array
    {
        return ['handled_at' => 'datetime'];
    }

    public function registerMediaCollections(): void
    {
        // medical documents: private disk, never a public URL
        $this->addMediaCollection('files')->useDisk('local');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
