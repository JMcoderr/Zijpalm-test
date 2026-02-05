<?php

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

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

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

    /**
     * Get the user's full name
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => ucfirst($this->firstName) . ' ' . $this->lastName,
        );
    }

    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Checks if the user wants to recieve a specific notification
     *
     * @param UserNotifications $notification The notification to check
     * @return bool
     */
    public function wantsNotification(UserNotifications $notification): bool
    {
        return ($this->notifications & $notification->value) === $notification->value;
    }

    /**
     * Checks if the user does not want to recieve a specific notification
     *
     * @param UserNotifications $notification The notification to check
     * @return bool
     */
    public function doesntWantNotification(UserNotifications $notification): bool
    {
        return !$this->wantsNotification($notification);
    }

    /**
     * Sets a specific notification.
     * Note: this does not save the update
     *
     * @param UserNotifications $notification The notification to set
     */
    public function setNotification(UserNotifications $notification): void
    {
        $this->notifications |= $notification->value;
    }

    /**
     * Unset a specific notification.
     * Note: this does not save the update
     *
     * @param UserNotifications $notification The notification to unset
     * @return void
     */
    public function unsetNotification(UserNotifications $notification): void
    {
        $this->notifications &= ~$notification->value;
    }

    /**
     * Retrieve a query builder for users of a specific type.
     *
     * @param  \App\UserType  $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function getByType(UserType $type)
    {
        return self::query()->where('type', $type);
    }

    /**
     * Scope a query to only include users that are not soft deleted or deleted in the future.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNotSoftDeleted($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('deleted_at')
              ->orWhere('deleted_at', '>', now());
        });
    }

    /**
     * Scope a query to only include users that are soft deleted (deleted_at in the past).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSoftDeletedPast($query)
    {
        return $query->onlyTrashed()
                     ->where('deleted_at', '<=', now());
    }

    /**
     * Determine if the user can update their personalia.
     *
     * @return bool
     */
    public function canUpdatePersonalia(): bool
    {
        return $this->type !== UserType::Medewerker && $this->type !== UserType::System;
    }

    /**
     * Determine if the user has admin privileges.
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->is_admin;
    }

    /**
     * Check if the user is a specific type.
     *
     * @param UserType $type
     * @return bool
     */
    public function isType(UserType $type): bool
    {
        return $this->type === $type;
    }

//    public function sendPasswordResetNotification($token): void
//    {
//        $this->notify(new CustomResetPassword($token));
//    }
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new CustomResetPassword(
            $token,
            $this->email
        ));
    }
}
