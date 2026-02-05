<?php

use Carbon\Carbon;

/**
 * Format a given date into a string.
 *
 * This helper function formats a Carbon date instance into the format
 * "D MMMM" (e.g., "5 January 2026").
 *
 * @param \Carbon\Carbon $date The date to be formatted.
 * @return string The formatted date string.
 */
function formatDate($date)
{
    if(!$date){
        return null;
    }
    $date->locale('NL');
    return $date->isoFormat('D MMMM YYYY');
}

/**
 * Format a given time into a string.
 *
 * This helper function formats a Carbon time instance into the format
 * "H:i" (e.g., "14:05").
 *
 * @param \Carbon\Carbon $time The time to be formatted.
 * @return string The formatted time string.
 */
function formatTime($time)
{
    if (!$time) {
        return null;
    }
    return $time->format('G:i');
}
