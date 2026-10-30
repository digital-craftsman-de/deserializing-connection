<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Profiler;

/**
 * Holds the data of every step of a single usage. The query duration is stored in nanoseconds and only converted when displayed.
 */
final class ProfilingTrace implements Trace
{
    public const string STAGE_RAW = 'raw';
    public const string STAGE_DECODED = 'decoded';
    public const string STAGE_TRANSFORMED = 'transformed';
    public const string STAGE_DENORMALIZED = 'denormalized';

    /** @var array<self::STAGE_*, mixed> */
    public private(set) array $stages = [];

    public private(set) ?int $queryDurationInNanoseconds = null;

    public private(set) ?\Throwable $exception = null;

    private readonly int $startedAt;

    /**
     * @param array<string, mixed>                $configuration
     * @param array{file: string, line: int}|null $caller
     */
    public function __construct(
        public readonly string $method,
        public readonly string $sql,
        public readonly ?string $runnableSql,
        public readonly ?string $runnableSqlError,
        public readonly array $configuration,
        public readonly ?array $caller,
    ) {
        $this->startedAt = hrtime(true);
    }

    #[\Override]
    public function recordRawResult(mixed $rawResult): void
    {
        $this->queryDurationInNanoseconds = hrtime(true) - $this->startedAt;
        $this->stages[self::STAGE_RAW] = $rawResult;
    }

    #[\Override]
    public function recordDecodedResult(mixed $decodedResult): void
    {
        $this->stages[self::STAGE_DECODED] = $decodedResult;
    }

    #[\Override]
    public function recordTransformedResult(mixed $transformedResult): void
    {
        $this->stages[self::STAGE_TRANSFORMED] = $transformedResult;
    }

    #[\Override]
    public function recordDenormalizedResult(mixed $denormalizedResult): void
    {
        $this->stages[self::STAGE_DENORMALIZED] = $denormalizedResult;
    }

    /**
     * @psalm-suppress MixedArrayAssignment The stage is only filled through this method in case of generators
     */
    #[\Override]
    public function addDenormalizedItem(object $denormalizedItem): void
    {
        $this->stages[self::STAGE_DENORMALIZED][] = $denormalizedItem;
    }

    #[\Override]
    public function recordException(\Throwable $exception): void
    {
        $this->exception = $exception;
    }
}
