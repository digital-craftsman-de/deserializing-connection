<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Profiler;

use Symfony\Bundle\FrameworkBundle\DataCollector\AbstractDataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\LateDataCollectorInterface;
use Symfony\Component\VarDumper\Cloner\Data;

/**
 * The traces are collected late, so that usages after the response was created (e.g. in streamed responses) are included as well.
 *
 * @psalm-type TraceData = array{
 *   method: string,
 *   sql: string,
 *   runnableSql: string|null,
 *   runnableSqlError: string|null,
 *   caller: array{file: string, line: int}|null,
 *   configuration: Data,
 *   queryDurationInNanoseconds: int|null,
 *   stages: array<string, Data>,
 *   exception: Data|null,
 *   exceptionClass: string|null,
 *   exceptionMessage: string|null,
 * }
 */
final class DeserializingConnectionDataCollector extends AbstractDataCollector implements LateDataCollectorInterface
{
    public const string NAME = 'deserializing_connection';

    public function __construct(
        private readonly ProfilingTracer $tracer,
    ) {
    }

    #[\Override]
    public function collect(
        Request $request,
        Response $response,
        ?\Throwable $exception = null,
    ): void {
    }

    #[\Override]
    public function lateCollect(): void
    {
        $this->data['traces'] = array_map(
            fn (ProfilingTrace $trace): array => [
                'method' => $trace->method,
                'sql' => $trace->sql,
                'runnableSql' => $trace->runnableSql,
                'runnableSqlError' => $trace->runnableSqlError,
                'caller' => $trace->caller,
                'configuration' => $this->cloneVar($trace->configuration),
                'queryDurationInNanoseconds' => $trace->queryDurationInNanoseconds,
                'stages' => array_map($this->cloneVar(...), $trace->stages),
                'exception' => $trace->exception !== null
                    ? $this->cloneVar($trace->exception)
                    : null,
                'exceptionClass' => $trace->exception !== null
                    ? $trace->exception::class
                    : null,
                'exceptionMessage' => $trace->exception?->getMessage(),
            ],
            $this->tracer->traces(),
        );
    }

    /**
     * @return list<TraceData>
     *
     * @psalm-suppress MixedReturnStatement
     */
    public function getTraces(): array
    {
        return $this->data['traces'] ?? [];
    }

    #[\Override]
    public function getName(): string
    {
        return self::NAME;
    }

    #[\Override]
    public static function getTemplate(): string
    {
        return '@DeserializingConnection/Collector/deserializing_connection.html.twig';
    }
}
