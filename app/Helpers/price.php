<?php

use App\QuestionType;

/**
 * Format a decimal number into a price string.
 *
 * @param float $price
 * @return string
 */
function formatPrice(float $price): string{
    // Returns '€-' if price is 0, otherwise, formats it to '€xx,-' or '€xx,xx' or even '€x.xxx,-'
    return '€' . ($price === 0.0 ? '-' : (floor($price) === $price ? number_format($price, 0, ',', '.') . ',-' : number_format($price, 2, ',', '.')));
}

// Formats a price for storing it in the database, doubly ensures that the value is formatted properly, yes, it's a bit redundant
function formatPriceForDb(string $price){
    return number_format((float)str_replace(',', '.', $price ?? 0), 2, '.', '');
}

/**
 * Calculates and formats the price based on the provided answer object.
 *
 * The price calculation depends on the type of the question associated with the answer:
 * - For text questions, the price is always 0.
 * - For checkbox questions, the price is the question's price if checked, otherwise 0.
 * - For number questions, the price is the answer multiplied by the question's price.
 * - For select questions, the price is determined by the selected option's price.
 * - For any other question type, the price defaults to 0.
 *
 * @param object $answer The answer object containing the user's response and related question.
 * @return float The calculated price based on the answer and question type.
 */
function getAnswerPrice($answer): float {
    switch ($answer->question->type) {
        case QuestionType::Text:
            return 0.0;
        case QuestionType::Checkbox:
            return $answer->answer ? $answer->question->price : 0.0;
        case QuestionType::Number:
            return $answer->answer * $answer->question->price;
        case QuestionType::Select:
            $option = $answer->question->selectOptions->firstWhere('option', $answer->answer);
            return isset($option->price) ? $option->price : 0.0;
        default:
            return 0.0;
    }
}