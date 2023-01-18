<?php

namespace Recca0120\StreamingResponse\Tests;

use Illuminate\Support\Facades\Storage;

class StreamingResponseServiceProviderTest extends TestCase
{
    public function test_streaming(): void
    {
        $this->expectOutputString('hello world');

        $disk = Storage::disk('public');

        $path = 'test.txt';
        $disk->put($path, 'hello world');

        $disk->streaming($path)->send();
    }
}