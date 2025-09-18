<?php

namespace SonPhoenix\VideoTools\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use SonPhoenix\VideoTools\VideoToolsServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
        // No factories for now since package has no models
    }

    protected function getPackageProviders($app)
    {
        return [
            VideoToolsServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
    }
}