<?php

/**
 * Format a phone number to 8 digits with a 06 prefix.
 *
 * @param string $number
 * @return string
 */
function formatPhoneNumber(?string $number): string
{
    if ($number) {
        return '06' . substr(preg_replace('/\D/', '', $number), -8);
    } else {
        return '06';
    }
}
