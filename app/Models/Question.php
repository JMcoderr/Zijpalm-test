<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\QuestionType;

class Question extends Model
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;

    protected $fillable = [
        'type',
        'query',
        'price',
        'max_amount',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'type' => QuestionType::class,
        'price' => 'decimal:2',
    ];

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    public function selectOptions()
    {
        return $this->hasMany(QuestionSelect::class);
    }

    // Function to return all the options' options and prices
    public function allOptions(): array
    {
        return $this->selectOptions->map(
            fn($option) =>[
                'id' => $option->id,
                'option' => $option->option,
                'price' => $option->price,
            ]
        )->toArray();
    }

    public function answers()
    {
        return $this->hasMany(Answer::class);
    }
}
