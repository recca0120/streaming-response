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

    public function test_s3_streaming(): void
    {
        if (empty($_ENV['AWS_ACCESS_KEY_ID'])) {
            $this->markTestSkipped();
        }

        config()->set('filesystems.disks.s3.key', $_ENV['AWS_ACCESS_KEY_ID']);
        config()->set('filesystems.disks.s3.secret', $_ENV['AWS_SECRET_ACCESS_KEY']);
        config()->set('filesystems.disks.s3.region', $_ENV['AWS_DEFAULT_REGION']);
        config()->set('filesystems.disks.s3.bucket', $_ENV['AWS_BUCKET']);

        Storage::disk('s3')->streaming('video/00494456-0511-4c3f-bdea-7e2cbb1915d4.mp4')->send();
    }
}
