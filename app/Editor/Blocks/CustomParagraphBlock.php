<?php

namespace App\Editor\Blocks;

use BumpCore\EditorPhp\Blocks\Paragraph;

class CustomParagraphBlock extends Paragraph
{
    public function allows(): array|string
    {
        $allowed = parent::allows();
        $allowed['text'][] = 'br';

        return $allowed;
    }
}