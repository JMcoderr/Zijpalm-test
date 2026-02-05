<?php

namespace App\Models;

use BumpCore\EditorPhp\EditorPhp;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\FileType;
use Illuminate\Support\Facades\Storage;

class Content extends Model
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;

    protected $fillable = [
        'type',
        'name',
        'fileType',
        'filePath',
        'title',
        'text'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'fileType' => FileType::class,
    ];

    /**
     * Get the image URL for the content.
     */
    protected function file(): Attribute
    {
        return Attribute::make(
            get: fn () => Storage::url($this->filePath),
        );
    }

    /**
     * Convert the description to HTML
     */
    protected function textHTML(): Attribute
    {
        return Attribute::make(
            get: fn () => EditorPhp::make($this->text)->toHtml()
        );
    }

    /**
     * Convert the description to HTML
     */
    protected function displayName(): Attribute
    {
        return Attribute::make(
            get: fn () => kebab_to_display($this->name)
        );
    }

    public function report(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Report::class);
    }

    /**
     * Retrieve all contents of a specific type
     *
     * @param  string $type
     * @return \Illuminate\Support\Collection<\App\Models\Content>
     */
    public static function getByType(string $type)
    {
        return self::where('type', $type)->get();
    }
}
