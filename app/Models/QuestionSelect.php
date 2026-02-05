<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class QuestionSelect extends Model
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;

    public $timestamps = false;
    
    protected $fillable = [
        'option',
        'price',
    ];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
