<?php

namespace Recca0120\StreamingResponse;

use Aws\S3\S3Client;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Arr;
use League\Flysystem\AwsS3v3\AwsS3Adapter;

class StreamingFile
{
    public function __construct(private FilesystemAdapter $disk, private string $path)
    {
    }

    public function size(): int
    {
        return $this->disk->size($this->path);
    }

    public function stream()
    {
        return $this->isS3() ? $this->getS3ReadStream() : $this->disk->readStream($this->path);
    }

    public function mimeType(): ?string
    {
        return $this->disk->mimeType($this->path);
    }

    public function lastModified(): int
    {
        return $this->disk->lastModified($this->path);
    }

    public function checksum(): string
    {
        return method_exists($this->disk, 'checksum')
            ? $this->disk->checksum($this->path)
            : md5(serialize($this->disk->getMetadata($this->path)));
    }

    protected function getS3ReadStream()
    {
        $client = $this->getS3Client();
        $client->registerStreamWrapper();
        $bucket = $this->getBucket();
        $context = stream_context_create(['s3' => ['seekable' => true]]);

        return fopen("s3://{$bucket}/{$this->path}", 'rb', false, $context);
    }

    /**
     * @return bool
     */
    private function isS3(): bool
    {
        if ($this->disk instanceof AwsS3V3Adapter) {
            return true;
        }

        $driver = $this->disk->getDriver();

        return method_exists($driver, 'getAdapter') && $driver->getAdapter() instanceof AwsS3Adapter;
    }

    /**
     * @return S3Client
     */
    private function getS3Client(): S3Client
    {
        if ($this->disk instanceof AwsS3V3Adapter) {
            return $this->disk->getClient();
        }

        return $this->disk->getAdapter()->getClient();
    }

    private function getBucket(): string
    {
        if ($this->disk instanceof AwsS3V3Adapter) {
            return Arr::get($this->disk->getConfig(), 'bucket');
        }

        return $this->disk->getAdapter()->getBucket();
    }
}
