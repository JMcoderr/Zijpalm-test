<?php
// This file is part of the app logic and has a short comment so it is easier to read.


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use App\PaymentStatus;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'product_type',
        'description',
        'price',
        'guest_price',
        'stock',
        'max_participants',
        'imagePath',
        'pickupLocation',
        'hidden',
        'allow_guest_checkout',
        'registration_starts_at',
        'registration_ends_at',
        'activity_starts_at',
        'activity_ends_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'guest_price' => 'decimal:2',
        'hidden' => 'boolean',
        'allow_guest_checkout' => 'boolean',
        'registration_starts_at' => 'date',
        'registration_ends_at' => 'date',
        'activity_starts_at' => 'datetime',
        'activity_ends_at' => 'datetime',
    ];

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_products')->withPivot('quantity', 'unit_price');
    }

    public function isActivityProduct(): bool
    {
        return $this->product_type === 'activity';
    }

    public function displayGuestPrice(): float
    {
        return (float) ($this->guest_price ?? $this->price);
    }

    public function scopeVisibleTo(Builder $query, bool $admin): Builder
    {
        return $admin ? $query : $query->where('hidden', false);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where(function (Builder $query) use ($type) {
            $query->where('product_type', $type);

            if ($type === 'product') {
                $query->orWhereNull('product_type');
            }
        });
    }

    public function availableQuantity(): ?int
    {
        $stock = $this->stock === null ? null : (int) $this->stock;

        if (!$this->isActivityProduct() || !$this->max_participants) {
            return $stock;
        }

        $reserved = (int) $this->orders()->whereHas('payment', fn (Builder $query) => $query
            ->whereIn('status', [PaymentStatus::open, PaymentStatus::pending, PaymentStatus::authorized, PaymentStatus::paid]))
            ->sum('order_products.quantity');
        $capacity = max(0, (int) $this->max_participants - $reserved);

        return $stock === null ? $capacity : min($stock, $capacity);
    }
}
