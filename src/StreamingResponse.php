<?php

namespace Recca0120\StreamingResponse;

use DateTime;
use LogicException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class StreamingResponse extends Response
{
    private int $offset = 0;

    private int $maxLength = -1;

    private int $chunkSize = 1024 * 8;

    private bool $streamed = false;

    private bool $headersSent = false;

    public function __construct(private StreamingFile $file, int $status = 200, array $headers = [])
    {
        parent::__construct(null, $status, $headers);

        $this->setLastModified(DateTime::createFromFormat('U', $this->file->lastModified()));
        $this->setEtag($this->file->checksum());
        $this->setPublic();
    }

    public function prepare(Request $request): static
    {
        if ($this->isInformational() || $this->isEmpty()) {
            parent::prepare($request);

            $this->maxLength = 0;

            return $this;
        }

        if (! $this->headers->has('Content-Type')) {
            $this->headers->set('Content-Type', $this->file->mimeType() ?: 'application/octet-stream');
        }

        parent::prepare($request);

        $this->offset = 0;
        $this->maxLength = -1;

        $fileSize = $this->file->size();
        $this->headers->remove('Transfer-Encoding');
        $this->headers->set('Content-Length', $fileSize);

        if (! $this->headers->has('Accept-Ranges')) {
            $this->headers->set('Accept-Ranges', $request->isMethodSafe() ? 'bytes' : 'none');
        }

        if (! $request->headers->has('Range') || ! $request->isMethod('GET')) {
            $this->headers->set('Content-Length', $fileSize);

            return $this;
        }

        $range = $request->headers->get('Range');
        preg_match('/(?<unit>\w+)=(?<offset>\d+)-(?<end>\d+)?/', $range, $matched);
        $end = (int) ($matched['end'] ?? $fileSize - 1);

        if (! array_key_exists('offset', $matched)) {
            $start = $fileSize - $end;
            $end = $fileSize - 1;
        } else {
            $start = (int) $matched['offset'];
        }

        if ($start > $end) {
            return $this;
        }

        $end = min($end, $fileSize - 1);
        if ($start < 0 || $start > $end) {
            $this->setStatusCode(416);
            $this->headers->set('Content-Range', sprintf('bytes */%s', $fileSize));
        } elseif ($end - $start < $fileSize - 1) {
            $this->setStatusCode(206);
            $this->headers->set('Content-Range', sprintf('bytes %s-%s/%s', $start, $end, $fileSize));
            $this->headers->set('Content-Length', $end - $start + 1);

            $this->maxLength = $end < $fileSize ? $end - $start + 1 : -1;
            $this->offset = $start;
        }

        if ($request->isMethod('HEAD')) {
            $this->maxLength = 0;
        }

        return $this;
    }

    public function sendHeaders(): static
    {
        if ($this->headersSent) {
            return $this;
        }

        $this->headersSent = true;

        return parent::sendHeaders();
    }

    public function sendContent(): static
    {
        if ($this->streamed) {
            return $this;
        }

        $this->streamed = true;

        if (0 === $this->maxLength) {
            return $this;
        }

        ignore_user_abort(true);

        $out = fopen('php://output', 'wb');
        $file = $this->file->stream();

        if ($this->offset > 0) {
            fseek($file, $this->offset);
        }

        $length = $this->maxLength;
        while ($length && ! feof($file)) {
            $read = ($length > $this->chunkSize) ? $this->chunkSize : $length;
            $length -= $read;

            stream_copy_to_stream($file, $out, $read);

            if (connection_aborted()) {
                break;
            }
        }

        fclose($out);
        fclose($file);

        return $this;
    }

    public function setContent(?string $content): static
    {
        if (null !== $content) {
            throw new LogicException('The content cannot be set on a BinaryFileResponse instance.');
        }

        return $this;
    }

    public function getContent(): string|false
    {
        return false;
    }
}
