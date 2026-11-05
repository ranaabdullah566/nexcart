<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    private string $lockFile = '';

    protected function setUp(): void
    {
        parent::setUp();

        // tests must never leave an "installed" lock in the real storage folder
        $this->lockFile = sys_get_temp_dir().'/nexcart-test-'.getmypid().'-'.bin2hex(random_bytes(3)).'.lock';
        config(['nexcart.lock_file' => $this->lockFile]);
    }

    protected function tearDown(): void
    {
        @unlink($this->lockFile);
        parent::tearDown();
    }
}
