<?php

namespace Database\Factories;

use App\QuestionType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Answer>
 */
class AnswerFactory extends Factory
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

    public function answer($question): static
    {
        switch($question->type) {
            case QuestionType::Text:
                $answer = fake()->text(100);
                break;
            case QuestionType::Number:
                $answer = fake()->randomNumber(2);
                break;
            case QuestionType::Select:
                $answer = fake()->randomElement($question->selectOptions);
                break;
            case QuestionType::Checkbox:
                $answer = rand(0, 1);
                break;
            default:
                $answer = '';
        }


        return $this->state(fn (array $attributes) => [
            'question_id' => $question->id,
            'answer' => $answer,
        ]);
    }
}
