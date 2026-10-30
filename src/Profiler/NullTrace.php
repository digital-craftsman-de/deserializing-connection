<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Profiler;

final readonly class NullTrace implements Trace
{
    #[\Override]
    public function recordRawResult(mixed $rawResult): void
    {
    }

    #[\Override]
    public function recordDecodedResult(mixed $decodedResult): void
    {
    }

    #[\Override]
    public function recordTransformedResult(mixed $transformedResult): void
    {
    }

    #[\Override]
    public function recordDenormalizedResult(mixed $denormalizedResult): void
    {
    }

    #[\Override]
    public function addDenormalizedItem(object $denormalizedItem): void
    {
    }

    #[\Override]
    public function recordException(\Throwable $exception): void
    {
    }
}
