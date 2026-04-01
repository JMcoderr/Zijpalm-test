<?php

namespace App\Editor\Blocks;

use BumpCore\EditorPhp\Blocks\Paragraph;

class CustomParagraphBlock extends Paragraph
{
    /**
     * Preserve line breaks from pasted plain text (e.g. Word) inside paragraph blocks.
     */
    public function render(): string
    {
        $text = (string) $this->get('text', '');

        $this->set('text', nl2br($text, false));

        return parent::render();
    }
}
