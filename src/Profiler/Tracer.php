<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Profiler;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Type;

/**
 * Starts a trace for every usage of the DeserializingConnection or DecodingConnection. The traces are used to show the single steps of
 * every usage in the Symfony profiler.
 */
interface Tracer
{
    /**
     * @param list<mixed>|array<string, mixed>                                        $parameters
     * @param array<int<0, max>|string, ArrayParameterType|ParameterType|Type|string> $parameterTypes
     * @param array<string, mixed>                                                    $configuration
     */
    public function startTrace(
        string $method,
        string $sql,
        array $parameters,
        array $parameterTypes,
        array $configuration,
    ): Trace;
}
