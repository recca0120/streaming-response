<?php

namespace Recca0120\StreamingResponse\Tests;

use Illuminate\Filesystem\FilesystemAdapter;
use League\Flysystem\FilesystemInterface;
use Mockery as m;
use Recca0120\StreamingResponse\StreamingFile;
use Recca0120\StreamingResponse\StreamingResponse;
use Symfony\Component\HttpFoundation\Request;

class StreamingResponseTest extends TestCase
{
    public function test_request_without_range(): void
    {
        $this->expectOutputString('hello world');

        $file = $this->givenFile();
        $request = Request::createFromGlobals();

        $response = new StreamingResponse($file);
        $response->prepare($request);

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals(11, $response->headers->get('content-length'));
        self::assertEquals('text/plain; charset=UTF-8', $response->headers->get('content-type'));

        $response->sendContent();
    }

    public function test_request_has_range(): void
    {
        $this->expectOutputString('world');

        $file = $this->givenFile();
        $request = Request::createFromGlobals();

        $response = new StreamingResponse($file);
        $request->headers->set('Range', 'bytes=6-10');
        $response->prepare($request);

        self::assertEquals(206, $response->getStatusCode());
        self::assertEquals('bytes 6-10/11', $response->headers->get('content-range'));
        self::assertEquals('5', $response->headers->get('content-length'));
        self::assertEquals('text/plain; charset=UTF-8', $response->headers->get('content-type'));

        $response->sendContent();
    }

    public function test_request_has_range_without_end(): void
    {
        $this->expectOutputString('world');

        $file = $this->givenFile();
        $request = Request::createFromGlobals();

        $response = new StreamingResponse($file);
        $request->headers->set('Range', 'bytes=6-');
        $response->prepare($request);

        self::assertEquals(206, $response->getStatusCode());
        self::assertEquals('bytes 6-10/11', $response->headers->get('content-range'));
        self::assertEquals('5', $response->headers->get('content-length'));
        self::assertEquals('text/plain; charset=UTF-8', $response->headers->get('content-type'));

        $response->sendContent();
    }

    public function test_request_has_range_without_offset_and_end(): void
    {
        $this->expectOutputString('ello world');

        $file = $this->givenFile();
        $request = Request::createFromGlobals();

        $response = new StreamingResponse($file);
        $request->headers->set('Range', 'bytes=');
        $response->prepare($request);

        self::assertEquals(206, $response->getStatusCode());
        self::assertEquals('bytes 1-10/11', $response->headers->get('content-range'));
        self::assertEquals('10', $response->headers->get('content-length'));
        self::assertEquals('text/plain; charset=UTF-8', $response->headers->get('content-type'));

        $response->sendContent();
    }

    public function test_request_has_range_offset_is_zero(): void
    {
        $this->expectOutputString('hello world');

        $file = $this->givenFile();
        $request = Request::createFromGlobals();

        $response = new StreamingResponse($file);
        $request->headers->set('Range', 'bytes=0-');
        $response->prepare($request);

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('text/plain; charset=UTF-8', $response->headers->get('content-type'));

        $response->sendContent();
    }

    /**
     * @return StreamingFile
     */
    private function givenFile(): StreamingFile
    {
        $stream = tmpfile();
        fwrite($stream, 'hello world');
        rewind($stream);
        $meta = stream_get_meta_data($stream);
        $path = $meta['uri'];

        $driver = m::mock(FilesystemInterface::class);

        $adapter = m::mock(FilesystemAdapter::class);
        $adapter->allows('size')->andReturn(filesize($path));
        $adapter->allows('readStream')->andReturn($stream);
        $adapter->allows('mimeType')->andReturn('text/plain');
        $adapter->allows('lastModified')->andReturn(filemtime($path));
        $adapter->allows('checksum')->andReturn(md5($path));
        $adapter->allows('getMetadata')->andReturn([
            'type' => 'file',
            'path' => 'test.txt',
            'timestamp' => 1674021816,
            'size' => 11,
        ]);
        $adapter->allows('getDriver')->andReturn($driver);

        return new StreamingFile($adapter, $path);
    }
}
