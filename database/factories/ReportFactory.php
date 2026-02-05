<?php

namespace Database\Factories;

use App\FileType;
use App\Models\Content;
use App\Models\Activity;
use App\Models\Report;
use BumpCore\EditorPhp\EditorPhp;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Report>
 */
class ReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            //
        ];
    }
    
    public function generateYearly(Carbon $year = null){
        return $this->state(
            function(array $attributes){
                // Generate an unique year per report
                $year = $this->faker->unique()->dateTimeBetween(now()->startOfMillennium(),now()->subYear())->format('Y');
        
                // Create associated content
                $content = Content::create([
                    'type' => 'report',
                    'name' => "report-yearly-$year",
                    'title' => "Jaarverslag $year",
                    'text' => EditorPhp::fake(false, 1, 5),
                    'filePath' => '/content/privacy.pdf',
                    'fileType' => FileType::Pdf,
                ]);
        
                return [
                    'activity_id' => null,
                    'content_id' => $content->id,
                    'year' => $year,
                ];
            }
        );
    }

    public function fromActivity(Activity $activity): static
    {
        $content = Content::create([
            'type' => 'report',
            'name' => "report-activity-$activity->id",
            'title' => $activity->title,
            'text' => EditorPhp::fake(false, 1, 5),
            'filePath' => '/images/logo.png',
            'fileType' => FileType::Image,
        ]);

        return $this->state(fn (array $attributes) => [
            'activity_id' => $activity->id,
            'content_id' => $content->id,
            'year' => null,
        ]);
    }
}
