<?php

namespace App\Models;

use App\Support\BusinessProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Order extends Model implements HasMedia
{
    use InteractsWithMedia;

    public const STATUSES = ['pending', 'confirmed', 'preparing', 'out_for_delivery', 'ready', 'completed', 'cancelled', 'refunded'];

    public const PAYMENT_STATUSES = ['unpaid', 'paid', 'failed', 'refunded'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_pickup' => 'boolean', 'tax_inclusive' => 'boolean', 'requires_prescription' => 'boolean',
            'subtotal' => 'decimal:2', 'discount' => 'decimal:2', 'shipping_cost' => 'decimal:2', 'tax' => 'decimal:2', 'total' => 'decimal:2', 'tax_rate' => 'decimal:2', 'points_discount' => 'decimal:2',
            'placed_at' => 'datetime', 'paid_at' => 'datetime', 'completed_at' => 'datetime', 'cancelled_at' => 'datetime',
            'eta_at' => 'datetime', 'rider_assigned_at' => 'datetime', 'prescription_reviewed_at' => 'datetime', 'slot_date' => 'date',
        ];
    }

    public function registerMediaCollections(): void
    {
        // private disk: prescriptions are medical documents and must never have a public URL
        $this->addMediaCollection('prescriptions')->useDisk('local');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(OrderEvent::class)->orderBy('id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rider_id');
    }

    public function prescriptionReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prescription_reviewed_by');
    }

    /** Needs a pharmacist's approval before it may be prepared or dispatched. */
    public function awaitsPrescription(): bool
    {
        return $this->requires_prescription && $this->prescription_status !== 'approved';
    }

    /** NC-100001, NC-100002 … prefix comes from the store name's initials. */
    public static function nextNumber(): string
    {
        $prefix = strtoupper(preg_replace('/[^A-Za-z]/', '', Str::ascii((string) setting('store.name', 'NexCart')))) ?: 'NC';
        $prefix = substr($prefix, 0, 3);
        $next = (int) static::max('id') + 100001;

        return $prefix.'-'.$next;
    }

    public static function newToken(): string
    {
        return Str::random(40);
    }

    /**
     * Statuses an order may move to from where it is now.
     * Pickup: preparing → ready → completed.  Delivery: preparing → out for delivery → completed,
     * and with riders on, delivery goes preparing → ready (waiting for a rider) → out for delivery → completed.
     */
    public function nextStatuses(): array
    {
        $viaRider = ! $this->is_pickup && BusinessProfile::hasModule('riders');

        return match ($this->status) {
            'pending' => ['confirmed', 'cancelled'],
            'confirmed' => ['preparing', 'cancelled'],
            'preparing' => [$this->is_pickup || $viaRider ? 'ready' : 'out_for_delivery', 'cancelled'],
            'ready' => [$viaRider ? 'out_for_delivery' : 'completed', 'cancelled'],
            'out_for_delivery' => ['completed', 'cancelled'],
            'completed' => ['refunded'],
            default => [],
        };
    }

    public function isOpen(): bool
    {
        return ! in_array($this->status, ['completed', 'cancelled', 'refunded'], true);
    }

    public function statusLabel(): string
    {
        return __(str_replace('_', ' ', ucfirst($this->status)));
    }

    /** Steps shown on the tracking timeline, in order. */
    public function timelineSteps(): array
    {
        return $this->is_pickup
            ? ['pending', 'confirmed', 'preparing', 'ready', 'completed']
            : ['pending', 'confirmed', 'preparing', 'out_for_delivery', 'completed'];
    }

    /** Position on the timeline; "ready" for a delivery means food is waiting for a rider, so it still counts as preparing. */
    public function timelineIndex(): int|false
    {
        $status = (! $this->is_pickup && $this->status === 'ready') ? 'preparing' : $this->status;

        return array_search($status, $this->timelineSteps(), true);
    }

    public function trackUrl(): string
    {
        return route('store.order', [$this->number, $this->public_token]);
    }
}
