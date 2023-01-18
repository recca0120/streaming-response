<?php

namespace Recca0120\StreamingResponse;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\ServiceProvider;


class StreamingResponseServiceProvider extends ServiceProvider
{
    public function boot()
    {
        FilesystemAdapter::macro('streaming', function (string $path) {
            return (new StreamingResponse(new StreamingFile($this, $path)))->prepare(request());
        });
    }
}