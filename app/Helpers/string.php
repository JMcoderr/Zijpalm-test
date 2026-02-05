<?php

function kebab_to_display($name) {
    // Replace hyphens and underscores with spaces, lowercase, then capitalize words
    return ucwords(str_replace(['-', '_'], ' ', strtolower($name)));
}