<?php
// Function to format names according to Dutch stylings
function formatName($firstName, $lastName){
    if(empty($firstName) || empty($lastName)){
        return '';
    }

    $formatChunk = function($str){
        $str = preg_replace('/\s+/', ' ', trim($str));
        $parts = array_filter(explode('-', $str), 'strlen');
        if(empty($parts)){
            return '';
        }

        $formatted = [];
        foreach($parts as $part){
            $words = explode(' ', trim($part));
            if(!empty($words)){
                $words[count($words) - 1] = ucfirst($words[count($words) - 1]);
                $formatted[] = implode(' ', $words);
            }
        }

        return implode('-', $formatted);
    };

    $formattedFirst = $formatChunk($firstName);
    $formattedLast = $formatChunk($lastName);

    return $formattedFirst && $formattedLast ? $formattedFirst . ' ' . $formattedLast : ($formattedFirst ?: $formattedLast);
}
