<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Profiler;

use Symfony\Bundle\FrameworkBundle\DataCollector\AbstractDataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\LateDataCollectorInterface;
use Symfony\Component\VarDumper\Caster\ConstStub;
use Symfony\Component\VarDumper\Cloner\Data;

/**
 * The traces are collected late, so that usages after the response was created (e.g. in streamed responses) are included as well. The
 * query is highlighted when collecting, as the highlighting is done in PHP.
 *
 * @psalm-type TraceData = array{
 *   method: string,
 *   sql: string,
 *   runnableSql: string|null,
 *   runnableSqlError: string|null,
 *   highlightedSql: string,
 *   caller: array{file: string, line: int}|null,
 *   configuration: Data,
 *   stageDurationsInNanoseconds: array<string, int>,
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
        private readonly SqlHighlighter $sqlHighlighter,
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
                'highlightedSql' => $this->sqlHighlighter->highlight($trace->runnableSql ?? $trace->sql),
                'caller' => $trace->caller,
                'configuration' => $this->cloneVar(self::replaceEnums($trace->configuration)),
                'stageDurationsInNanoseconds' => $trace->stageDurationsInNanoseconds,
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
     * Enums (e.g. the decoder types) are shown the same way as they are written in the code (e.g. DecoderType::JSON) instead of as
     * objects. The fully qualified name is shown as title.
     */
    private static function replaceEnums(mixed $value): mixed
    {
        if ($value instanceof \UnitEnum) {
            return new ConstStub(
                sprintf('%s::%s', new \ReflectionClass($value)->getShortName(), $value->name),
                sprintf('%s::%s', $value::class, $value->name),
            );
        }

        if (is_array($value)) {
            return array_map(self::replaceEnums(...), $value);
        }

        return $value;
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
