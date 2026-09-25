<?php
// This file is part of the app logic and has a short comment so it is easier to read.


function kebab_to_display($name) {
    // Replace hyphens and underscores with spaces, lowercase, then capitalize words
    return ucwords(str_replace(['-', '_'], ' ', strtolower($name)));
}

// Remove website-only CSS classes before HTML is sent in an email.
function prepareMailHtml(string $html): string
{
    $html = preg_replace('/\sclass\s*=\s*("|\').*?\1/is', '', $html) ?? $html;

    $previous = libxml_use_internal_errors(true);
    $document = new DOMDocument('1.0', 'UTF-8');
    $document->loadHTML('<?xml encoding="UTF-8"><div id="mail-content">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    $xpath = new DOMXPath($document);
    foreach ($xpath->query('//a') as $link) {
        $linkText = trim($link->textContent);
        $hasBlockContent = $xpath->query('.//*[self::p or self::div or self::h1 or self::h2 or self::h3 or self::h4 or self::h5 or self::h6 or self::ul or self::ol or self::li]', $link)->length > 0;

        if ($hasBlockContent || mb_strlen($linkText) > 100) {
            while ($link->firstChild) {
                $link->parentNode->insertBefore($link->firstChild, $link);
            }
            $link->parentNode->removeChild($link);
        }
    }

    $container = $document->getElementById('mail-content');
    if (!$container) {
        return $html;
    }

    $result = '';
    foreach ($container->childNodes as $child) {
        $result .= $document->saveHTML($child);
    }

    return $result;
}

// Remove links from activity descriptions so malformed editor links cannot break the mail.
function prepareMailDescriptionHtml(string $html): string
{
    $html = prepareMailHtml($html);

    $previous = libxml_use_internal_errors(true);
    $document = new DOMDocument('1.0', 'UTF-8');
    $document->loadHTML('<?xml encoding="UTF-8"><div id="mail-description">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    $xpath = new DOMXPath($document);
    foreach ($xpath->query('//a') as $link) {
        while ($link->firstChild) {
            $link->parentNode->insertBefore($link->firstChild, $link);
        }
        $link->parentNode->removeChild($link);
    }

    $container = $document->getElementById('mail-description');
    if (!$container) {
        return $html;
    }

    $result = '';
    foreach ($container->childNodes as $child) {
        $result .= $document->saveHTML($child);
    }

    return $result;
}