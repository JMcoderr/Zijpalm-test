<?php

namespace Database\Seeders;

use App\Models\Report;
use App\Models\Question;
use App\Models\QuestionSelect;
use App\QuestionType;
use App\ActivityType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Activity;

class ActivitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create normal activities
        Activity::factory(30)->create()->each(function ($activity) {
            // For each activity, create a random number of questions (0-3)
            Question::factory(rand(1,4))->create()->each( function ($question) {
                // For each question, if the question is of type select, create a random number of select options (2-5)
                if ($question->type == QuestionType::Select) {
                    QuestionSelect::factory(rand(2, 5))->create([
                        'question_id' => $question->id,
                    ]);
                }
            });
            // For each activity, create a report, or not 🤷
            if(rand(0,9) > 0){
                Report::factory()->fromActivity($activity)->create();
            }
        });

        // Create weekly activities
        Activity::factory(3)->create([
            'start' => null,
            'end' => null,
            'registrationStart' => null,
            'registrationEnd' => null,
            'cancellationEnd' => null,
            'type' => ActivityType::Weekly,
        ]);
    }
}
