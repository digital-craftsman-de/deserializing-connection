<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Profiler;

use DigitalCraftsman\DeserializingConnection\Serializer\DecodingConnection;
use DigitalCraftsman\DeserializingConnection\Serializer\DeserializingConnection;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Collects the traces of all usages within a request. Only used when the Symfony profiler is available.
 */
final class ProfilingTracer implements Tracer, ResetInterface
{
    /** @var list<ProfilingTrace> */
    private array $traces = [];

    public function __construct(
        private readonly RunnableQueryRenderer $runnableQueryRenderer,
    ) {
    }

    #[\Override]
    public function startTrace(
        string $method,
        string $sql,
        array $parameters,
        array $parameterTypes,
        array $configuration,
    ): ProfilingTrace {
        $runnableSql = null;
        $runnableSqlError = null;
        try {
            $runnableSql = $this->runnableQueryRenderer->render($sql, $parameters, $parameterTypes);
        } catch (\Throwable $exception) {
            $runnableSqlError = $exception->getMessage();
        }

        $trace = new ProfilingTrace(
            method: $method,
            sql: $sql,
            runnableSql: $runnableSql,
            runnableSqlError: $runnableSqlError,
            configuration: $configuration,
            caller: self::caller(),
        );

        $this->traces[] = $trace;

        return $trace;
    }

    /**
     * @return list<ProfilingTrace>
     */
    public function traces(): array
    {
        return $this->traces;
    }

    #[\Override]
    public function reset(): void
    {
        $this->traces = [];
    }

    /**
     * The caller is the location from where the DeserializingConnection or DecodingConnection was called.
     *
     * @return array{file: string, line: int}|null
     */
    private static function caller(): ?array
    {
        foreach (debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS) as $frame) {
            if (!in_array($frame['class'] ?? null, [DeserializingConnection::class, DecodingConnection::class], true)) {
                continue;
            }
            // Not available when called through an internal function like array_map
            if (!isset($frame['file'], $frame['line'])) {
                return null;
            }

            return [
                'file' => $frame['file'],
                'line' => $frame['line'],
            ];
        }

        return null;
    }
}
