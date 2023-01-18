<?php

namespace Recca0120\StreamingResponse\Tests;


use Illuminate\Support\Facades\Storage;
use Recca0120\StreamingResponse\StreamingFile;

class StreamingFileTest extends TestCase
{
    public function test_s3_driver(): void
    {
        config()->set('filesystems.disks.s3.region', 'us-west-2');
        config()->set('filesystems.disks.s3.bucket', 'foo');

        $file = new StubStreamingFile(Storage::disk('s3'), 'foo');
        $stream = $file->stream();

        self::assertEquals('php://output', stream_get_meta_data($stream)['uri']);
    }
}

class StubStreamingFile extends StreamingFile
{
    protected function getS3ReadStream()
    {
        return fopen('php://output', 'rb');
    }
}