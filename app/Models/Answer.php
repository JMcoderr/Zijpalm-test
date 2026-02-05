<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Answer extends Model
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'application_id', 
        'question_id', 
        'answer',
    ];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function application()
    {
        return $this->belongsTo(Application::class);
    }
}
