<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Profiler;

interface Trace
{
    public function recordRawResult(mixed $rawResult): void;

    public function recordDecodedResult(mixed $decodedResult): void;

    public function recordTransformedResult(mixed $transformedResult): void;

    public function recordDenormalizedResult(mixed $denormalizedResult): void;

    /**
     * Used for generators where the items are denormalized one after another.
     */
    public function addDenormalizedItem(object $denormalizedItem): void;

    public function recordException(\Throwable $exception): void;
}
