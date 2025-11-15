<?php

namespace DigitalCoreHub\Toon\Tests;

use DigitalCoreHub\Toon\ToonServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            ToonServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        // Setup any environment configuration if needed
    }
}
