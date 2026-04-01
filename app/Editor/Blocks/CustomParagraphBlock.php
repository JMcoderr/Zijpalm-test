<?php

namespace App\Editor\Blocks;

use BumpCore\EditorPhp\Blocks\Paragraph;

class CustomParagraphBlock extends Paragraph
{
    public function allows(): array|string
    {
        $allows = parent::allows();

        if (is_array($allows) && isset($allows['text']) && is_array($allows['text'])) {
            $allows['text'][] = 'br';
        }

        return $allows;
    }

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
