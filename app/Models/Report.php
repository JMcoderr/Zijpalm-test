<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class Report extends Model
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;

    protected $table = 'reports';
    protected $fillable = [
        'activity_id',
        'content_id',
        'archived',
        'year'
    ];

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    public function content()
    {
        return $this->belongsTo(Content::class);
    }

    /**
     * When deleting a Report check if it has a relation with Content
     * If so delete from storage the file associated, and the Content itself.
     *
     * @return void
     */
    protected static function booted()
    {
        static::deleting(function ($report) {
            if ($report->content) {
                Storage::disk('public')->delete($report->content->filePath);
                $report->content->delete();
            }
        });
    }
}
