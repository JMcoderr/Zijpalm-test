<?php

namespace App\Editor\Blocks;

use BumpCore\EditorPhp\Blocks\ListBlock;

class CustomListBlock extends ListBlock
{
    public function render(): string
    {
        // Check if data is an object with get() method
        $items = method_exists($this->data, 'get')
            ? $this->data->get('items', [])
            : [];

        // Normalize to flat strings
        $items = array_map(function ($item) {
            if (is_array($item) && isset($item['content'])) {
                return $item['content'];
            }
            return is_string($item) ? $item : '';
        }, $items);

        // Inject back into the data object
        if (method_exists($this->data, 'set')) {
            $this->data->set('items', $items);
        }

        return parent::render();
    }
}
