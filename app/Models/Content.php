<?php
// This file is part of the app logic and has a short comment so it is easier to read.


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
            get: function () {
                if (blank($this->text)) {
                    return '';
                }

                try {
                    $raw = (string) $this->text;

                    // Try progressively decoding HTML entities until we can decode JSON
                    $attempts = 0;
                    $maxAttempts = 5;
                    while ($attempts < $maxAttempts) {
                        $decoded = json_decode($raw, true);
                        if (is_array($decoded) && array_key_exists('blocks', $decoded)) {
                            return EditorPhp::make($raw)->toHtml();
                        }
                        $new = html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        if ($new === $raw) {
                            break;
                        }
                        $raw = $new;
                        $attempts++;
                    }

                    // Plain text fallback: decode any lingering HTML entities fully before escaping
                    $decodedText = (string) $this->text;
                    $prev = null;
                    $tries = 0;
                    while ($tries < $maxAttempts && $decodedText !== $prev) {
                        $prev = $decodedText;
                        $decodedText = html_entity_decode($decodedText, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        $tries++;
                    }

                    return '<p>' . e($decodedText) . '</p>';

                } catch (\Throwable $e) {
                    \Log::warning('[Content] textHTML fallback to plain text', [
                        'content_id' => $this->id,
                        'name'       => $this->name,
                        'error'      => $e->getMessage(),
                    ]);
                    return '<p>' . e((string) $this->text) . '</p>';
                }
            }
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
