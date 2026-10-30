<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Profiler;

/**
 * Used when the profiler isn't available (e.g. in production).
 */
final readonly class NullTracer implements Tracer
{
    #[\Override]
    public function startTrace(
        string $method,
        string $sql,
        array $parameters,
        array $parameterTypes,
        array $configuration,
    ): Trace {
        return new NullTrace();
    }
}
