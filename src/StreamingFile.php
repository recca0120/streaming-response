<?php

namespace Recca0120\StreamingResponse;

use Aws\S3\S3Client;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Arr;
use League\Flysystem\AwsS3v3\AwsS3Adapter;

class StreamingFile
{
    public function __construct(private FilesystemAdapter $adapter, private string $path)
    {

    }

    public function size(): int
    {
        return $this->adapter->size($this->path);
    }

    public function stream()
    {
        return $this->isS3() ? $this->getS3ReadStream() : $this->adapter->readStream($this->path);
    }

    public function mimeType(): ?string
    {
        return $this->adapter->mimeType($this->path);
    }

    public function lastModified(): int
    {
        return $this->adapter->lastModified($this->path);
    }

    public function checksum(): bool|string
    {
        return $this->adapter->checksum($this->path);
    }

    protected function getS3ReadStream()
    {
        $bucket = Arr::get($this->adapter->getConfig(), 'bucket');
        /** @var S3Client $client */
        $client = $this->adapter->getClient();
        $client->registerStreamWrapper();
        $context = stream_context_create(['s3' => ['seekable' => true]]);

        return fopen("s3://{$bucket}/{$this->path}", 'rb', false, $context);
    }

    /**
     * @return bool
     */
    private function isS3(): bool
    {
        if ($this->adapter instanceof AwsS3V3Adapter) {
            return true;
        }

        $driver = $this->adapter->getDriver();

        return method_exists($driver, 'getAdapter') && $driver->getAdapter() instanceof AwsS3Adapter;
    }
}
