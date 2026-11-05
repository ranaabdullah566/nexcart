<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Review extends Model
{
    use Audited;

    use SoftDeletes;

    public const STATUSES = ['pending', 'approved', 'hidden'];

    protected $fillable = ['product_id', 'order_id', 'user_id', 'name', 'rating', 'title', 'body', 'status', 'reply'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
