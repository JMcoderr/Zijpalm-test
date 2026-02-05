<?php

/***
 * Casts given keys in a dataset to ints
 * It's main use is casting validated batch_size & delay values from POST requests to integers for Power Automate parsing.
 *
 * @param array $data
 * @param array $keys
 * @return array
 */
function castValidatedInts(array $data, array $keys): array {
    foreach ($keys as $key) {
        if(isset($data[$key])) {
            $data[$key] = (int)$data[$key];
        }
    }
    return $data;
}
