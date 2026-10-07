<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Cache;

/**
 * Rotates across up to 4 Gemini API keys (config('ai.gemini.api_keys')).
 * When a key hits its quota, it is marked exhausted for the rest of the
 * day (Gemini free-tier quotas reset daily) so later requests skip
 * straight to the next working key instead of retrying a dead one.
 */
class GeminiKeyPool
{
    private const CACHE_PREFIX = 'gemini_key_exhausted_';

    public function hasAnyKey(): bool
    {
        return $this->keys() !== [];
    }

    /**
     * The first key that is not currently marked exhausted, or null if
     * every configured key is exhausted (or none are configured).
     */
    public function currentKey(): ?string
    {
        foreach ($this->keys() as $key) {
            if (! $this->isExhausted($key)) {
                return $key;
            }
        }

        return null;
    }

    /**
     * The next not-yet-exhausted key after $exhaustedKey, used for a
     * single retry right after marking a key exhausted.
     */
    public function nextKeyAfter(string $exhaustedKey): ?string
    {
        $keys = $this->keys();
        $position = array_search($exhaustedKey, $keys, true);

        if ($position === false) {
            return $this->currentKey();
        }

        for ($i = $position + 1; $i < count($keys); $i++) {
            if (! $this->isExhausted($keys[$i])) {
                return $keys[$i];
            }
        }

        return null;
    }

    public function markExhausted(string $key): void
    {
        Cache::put($this->cacheKeyFor($key), true, now()->endOfDay());
    }

    /**
     * Read individually (rather than a precomputed array) so that
     * Config::set('ai.gemini.api_key', ...) in tests is respected.
     *
     * @return array<int, string>
     */
    private function keys(): array
    {
        return array_values(array_filter([
            config('ai.gemini.api_key'),
            config('ai.gemini.api_key_2'),
            config('ai.gemini.api_key_3'),
            config('ai.gemini.api_key_4'),
        ], fn (mixed $key): bool => is_string($key) && trim($key) !== ''));
    }

    private function isExhausted(string $key): bool
    {
        return (bool) Cache::get($this->cacheKeyFor($key), false);
    }

    private function cacheKeyFor(string $key): string
    {
        return self::CACHE_PREFIX.substr(hash('sha256', $key), 0, 16);
    }
}
