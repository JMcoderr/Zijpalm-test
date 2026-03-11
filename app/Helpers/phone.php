<?php

/**
 * Format a phone number to 8 digits with a 06 prefix.
 * Accepts nullable and non-string legacy values safely.
 */
function formatPhoneNumber($number): string
{
    if ($number === null || $number === '') {
        return '06';
    }

    $digits = preg_replace('/\D/', '', (string) $number);

    if ($digits === '') {
        return '06';
    }

    return '06' . substr($digits, -8);
}
