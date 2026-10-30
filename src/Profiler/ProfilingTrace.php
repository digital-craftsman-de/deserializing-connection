<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Profiler;

/**
 * Holds the data of every step of a single usage. The duration of every step is measured from the end of the previous step (or the start
 * of the trace for the raw result, which is the query duration). The durations are stored in nanoseconds and only converted when
 * displayed.
 */
final class ProfilingTrace implements Trace
{
    public const string STAGE_RAW = 'raw';
    public const string STAGE_DECODED = 'decoded';
    public const string STAGE_TRANSFORMED = 'transformed';
    public const string STAGE_DENORMALIZED = 'denormalized';

    /** @var array<self::STAGE_*, mixed> */
    public private(set) array $stages = [];

    /** @var array<self::STAGE_*, int> */
    public private(set) array $stageDurationsInNanoseconds = [];

    public private(set) ?\Throwable $exception = null;

    private int $lastRecordedAt;

    /** @var list<int> */
    private array $itemDenormalizationDurationsInNanoseconds = [];

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
        $this->lastRecordedAt = hrtime(true);
    }

    #[\Override]
    public function recordRawResult(mixed $rawResult): void
    {
        $this->recordStage(self::STAGE_RAW, $rawResult);
    }

    #[\Override]
    public function recordDecodedResult(mixed $decodedResult): void
    {
        $this->recordStage(self::STAGE_DECODED, $decodedResult);
    }

    #[\Override]
    public function recordTransformedResult(mixed $transformedResult): void
    {
        $this->recordStage(self::STAGE_TRANSFORMED, $transformedResult);
    }

    #[\Override]
    public function recordDenormalizedResult(mixed $denormalizedResult): void
    {
        $this->recordStage(self::STAGE_DENORMALIZED, $denormalizedResult);
    }

    #[\Override]
    public function startItemDenormalization(): void
    {
        $this->lastRecordedAt = hrtime(true);
    }

    /**
     * The durations of all items are summed up.
     *
     * @psalm-suppress MixedArrayAssignment The stage is only filled through this method in case of generators
     */
    #[\Override]
    public function addDenormalizedItem(object $denormalizedItem): void
    {
        $now = hrtime(true);

        $this->stages[self::STAGE_DENORMALIZED][] = $denormalizedItem;
        $this->itemDenormalizationDurationsInNanoseconds[] = $now - $this->lastRecordedAt;
        $this->stageDurationsInNanoseconds[self::STAGE_DENORMALIZED] = array_sum($this->itemDenormalizationDurationsInNanoseconds);

        $this->lastRecordedAt = $now;
    }

    #[\Override]
    public function recordException(\Throwable $exception): void
    {
        $this->exception = $exception;
    }

    /**
     * @param self::STAGE_* $stage
     */
    private function recordStage(string $stage, mixed $result): void
    {
        $now = hrtime(true);

        $this->stages[$stage] = $result;
        $this->stageDurationsInNanoseconds[$stage] = $now - $this->lastRecordedAt;

        $this->lastRecordedAt = $now;
    }
}
