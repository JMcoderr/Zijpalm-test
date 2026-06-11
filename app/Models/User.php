<?php
// This file is part of the app logic and has a short comment so it is easier to read.


namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\CustomResetPassword;
use App\UserNotifications;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use App\UserType;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    // These are the fields that can be filled when creating or updating a user
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'firstName',
        'lastName',
        'email',
        'phone',
        'type',
        'employee_number',
        'contribution',
        'accepted_terms_at',
        'accepted_privacy_at',
        'password',
        'deleted_at',
    ];

    // These fields won't be shown when the user is converted to JSON or array
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // How to cast certain fields when retrieving from DB
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'accepted_terms_at' => 'datetime',
            'accepted_privacy_at' => 'datetime',
            'type' => UserType::class,
            'is_admin' => 'boolean',
            'contribution' => 'decimal:2',
            'notifications' => 'integer',
            'deleted_at' => 'datetime',
        ];
    }

    // Get the user's initials from first and last name
    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->map(fn (string $name) => Str::of($name)->substr(0, 1))
            ->implode('');
    }

    // Get the full name by combining first and last name
    /**
     * Get the user's full name
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => ucfirst($this->firstName) . ' ' . $this->lastName,
        );
    }

    // Relationship: a user can have many applications
    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    // Relationship: a user can have many orders
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    // Check if user wants this notification
    /**
     * Checks if the user wants to recieve a specific notification
     *
     * @param UserNotifications $notification The notification to check
     * @return bool
     */
    public function wantsNotification(UserNotifications $notification): bool
    {
        // The notifications field is used like a bitmask, so we check if the flag is enabled.
        return ($this->notifications & $notification->value) === $notification->value;
    }

    // Check if user doesn't want this notification
    /**
     * Checks if the user does not want to recieve a specific notification
     *
     * @param UserNotifications $notification The notification to check
     * @return bool
     */
    public function doesntWantNotification(UserNotifications $notification): bool
    {
        // This is just the opposite of wantsNotification, so the code stays easy to read.
        return !$this->wantsNotification($notification);
    }

    // Enable this notification for the user
    /**
     * Sets a specific notification.
     * Note: this does not save the update
     *
     * @param UserNotifications $notification The notification to set
     */
    public function setNotification(UserNotifications $notification): void
    {
        // Turn the notification flag on for this user.
        $this->notifications |= $notification->value;
    }

    // Disable this notification for the user
    /**
     * Unset a specific notification.
     * Note: this does not save the update
     *
     * @param UserNotifications $notification The notification to unset
     * @return void
     */
    public function unsetNotification(UserNotifications $notification): void
    {
        // Turn the notification flag off for this user.
        $this->notifications &= ~$notification->value;
    }

    // Get users of a specific type
    /**
     * Retrieve a query builder for users of a specific type.
     *
     * @param  \App\UserType  $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function getByType(UserType $type)
    {
        // Reuse this helper when we need all users of one specific type.
        return self::query()->where('type', $type);
    }

    // Scope for users that are not soft deleted
    /**
     * Scope a query to only include users that are not soft deleted or deleted in the future.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNotSoftDeleted($query)
    {
        // Keep users that are still active or deleted in the future.
        return $query->where(function ($q) {
            $q->whereNull('deleted_at')
              ->orWhere('deleted_at', '>', now());
        });
    }

    // Scope for users that are soft deleted in the past
    /**
     * Scope a query to only include users that are soft deleted (deleted_at in the past).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSoftDeletedPast($query)
    {
        // Only include users that were deleted in the past.
        return $query->onlyTrashed()
                     ->where('deleted_at', '<=', now());
    }

    // Check if user can update their personal info
    /**
     * Determine if the user can update their personalia.
     *
     * @return bool
     */
    public function canUpdatePersonalia(): bool
    {
        // Employees and system users are locked for personal data changes.
        return $this->type !== UserType::Medewerker && $this->type !== UserType::System;
    }

    // Check if user is admin
    /**
     * Determine if the user has admin privileges.
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        // Simple helper to check admin access.
        return (bool) $this->is_admin;
    }

    // Check if user is of a specific type
    /**
     * Check if the user is a specific type.
     *
     * @param UserType $type
     * @return bool
     */
    public function isType(UserType $type): bool
    {
        // Compare the stored enum directly with the given type.
        return $this->type === $type;
    }

//    public function sendPasswordResetNotification($token): void
//    {
//        $this->notify(new CustomResetPassword($token));
//    }

    // Send password reset notification
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new CustomResetPassword(
            $token,
            $this->email
        ));
    }
}
