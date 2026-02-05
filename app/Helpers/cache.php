<?php

use App\Models\Content;
use Illuminate\Support\Facades\Cache;

/**
 * Retrieves a value from the cache by the given key.
 * If the value does not exist in the cache, it queries the Content model
 * for the first record matching the provided key as the 'name' attribute
 *
 * @param string $key The cache key and Content name to retrieve.
 * @param int $ttl The time-to-live for the cache in seconds (default is 3600 seconds).
 * @return Content The cached Content model instance or null if not found.
 *
 * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If no Content model is found with the given name.
 */
function getFromCache(string $key, int $ttl = 3600):Content {
    return Cache::remember($key, $ttl, function() use ($key) {
        return Content::where('name', $key)->firstOrFail();
    });
}