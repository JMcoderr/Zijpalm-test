<?php
// This file is part of the app logic and has a short comment so it is easier to read.


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
}
