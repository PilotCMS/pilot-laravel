<?php

namespace Pilot\Laravel\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Pilot\Laravel\PilotServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [PilotServiceProvider::class];
    }
}
