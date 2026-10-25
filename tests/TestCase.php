<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Gunakan cache array agar rate limiter tidak menulis ke cache file aplikasi.
        config(['cache.default' => 'array']);
        Cache::flush();
    }
}
