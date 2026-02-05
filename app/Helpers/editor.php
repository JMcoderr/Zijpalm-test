<?php

/**
 * Format the Editor Data for the database
 *
 * @param string $editorData
 * @return string
 */
function decodeEditorData(string $editorData): string   {
    return html_entity_decode($editorData, ENT_QUOTES, 'UTF-8');
}