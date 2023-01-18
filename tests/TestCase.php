<?php

namespace Recca0120\StreamingResponse\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Recca0120\StreamingResponse\StreamingResponseServiceProvider;

class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [StreamingResponseServiceProvider::class];
    }
}