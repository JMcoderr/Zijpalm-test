<?php
// This file is part of the app logic and has a short comment so it is easier to read.


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
