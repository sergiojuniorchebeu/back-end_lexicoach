<?php

namespace Tests\Unit;

use App\Services\Ai\GeminiKeyPool;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class GeminiKeyPoolTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('ai.gemini.api_key', 'key-1');
        Config::set('ai.gemini.api_key_2', 'key-2');
        Config::set('ai.gemini.api_key_3', 'key-3');
        Config::set('ai.gemini.api_key_4', null);
    }

    public function test_current_key_is_the_first_configured_key(): void
    {
        $pool = new GeminiKeyPool;

        $this->assertTrue($pool->hasAnyKey());
        $this->assertSame('key-1', $pool->currentKey());
    }

    public function test_marking_a_key_exhausted_switches_to_the_next_one(): void
    {
        $pool = new GeminiKeyPool;

        $pool->markExhausted('key-1');

        $this->assertSame('key-2', $pool->currentKey());
    }

    public function test_next_key_after_skips_already_exhausted_keys(): void
    {
        $pool = new GeminiKeyPool;

        $pool->markExhausted('key-1');
        $pool->markExhausted('key-2');

        $this->assertSame('key-3', $pool->nextKeyAfter('key-1'));
    }

    public function test_returns_null_when_every_key_is_exhausted(): void
    {
        $pool = new GeminiKeyPool;

        $pool->markExhausted('key-1');
        $pool->markExhausted('key-2');
        $pool->markExhausted('key-3');

        $this->assertNull($pool->currentKey());
    }

    public function test_has_any_key_is_false_without_configuration(): void
    {
        Config::set('ai.gemini.api_key', null);
        Config::set('ai.gemini.api_key_2', null);
        Config::set('ai.gemini.api_key_3', null);

        $pool = new GeminiKeyPool;

        $this->assertFalse($pool->hasAnyKey());
        $this->assertNull($pool->currentKey());
    }
}
