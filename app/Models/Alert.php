<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One entry in a staff member's alerts inbox (every Laravel Notify message, new orders, low stock). */
class Alert extends Model
{
    protected $table = 'admin_alerts';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Alert every staff member who holds the permission. */
    public static function broadcast(string $permission, string $message, ?string $url = null, string $type = 'info', string $source = 'system'): void
    {
        try {
            User::permission($permission)->get()->each(fn (User $u) => static::create(['user_id' => $u->id, 'type' => $type, 'source' => $source, 'message' => mb_substr($message, 0, 500), 'url' => $url]));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
