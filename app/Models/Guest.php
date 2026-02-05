<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Guest extends Model
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'firstName',
        'lastName',
        'email',
        'phone',
        'adult',
    ];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * Get the guest's full name
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => ucfirst($this->firstName) . ' ' . $this->lastName,
        );
    }
}
