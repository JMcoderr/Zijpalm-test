<?php
// This file is part of the app logic and has a short comment so it is easier to read.


function kebab_to_display($name) {
    // Replace hyphens and underscores with spaces, lowercase, then capitalize words
    return ucwords(str_replace(['-', '_'], ' ', strtolower($name)));
}