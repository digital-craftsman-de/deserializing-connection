<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Profiler;

use DigitalCraftsman\DeserializingConnection\Test\ValueObject\UserId;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProfilingTrace::class)]
final class ProfilingTraceTest extends TestCase
{
    #[Test]
    public function construction_works(): void
    {
        // -- Act
        $trace = self::trace();

        // -- Assert
        self::assertSame('DeserializingConnection::findOne', $trace->method);
        self::assertSame('SELECT :id', $trace->sql);
        self::assertSame("SELECT '1'", $trace->runnableSql);
        self::assertNull($trace->runnableSqlError);
        self::assertSame(['class' => UserId::class], $trace->configuration);
        self::assertSame(['file' => __FILE__, 'line' => 10], $trace->caller);
        self::assertSame([], $trace->stages);
        self::assertSame([], $trace->stageDurationsInNanoseconds);
        self::assertNull($trace->exception);
    }

    #[Test]
    public function record_stages_works(): void
    {
        // -- Arrange
        $trace = self::trace();
        $userId = UserId::generateRandom();

        // -- Act
        $trace->recordRawResult(['id' => '1']);
        $trace->recordDecodedResult(['id' => 1]);
        $trace->recordTransformedResult(['id' => 2]);
        $trace->recordDenormalizedResult($userId);

        // -- Assert
        self::assertSame([
            ProfilingTrace::STAGE_RAW => ['id' => '1'],
            ProfilingTrace::STAGE_DECODED => ['id' => 1],
            ProfilingTrace::STAGE_TRANSFORMED => ['id' => 2],
            ProfilingTrace::STAGE_DENORMALIZED => $userId,
        ], $trace->stages);
    }

    #[Test]
    public function add_denormalized_item_works(): void
    {
        // -- Arrange
        $trace = self::trace();
        $userId1 = UserId::generateRandom();
        $userId2 = UserId::generateRandom();

        // -- Act
        $trace->addDenormalizedItem($userId1);
        $trace->addDenormalizedItem($userId2);

        // -- Assert
        self::assertSame([
            ProfilingTrace::STAGE_DENORMALIZED => [$userId1, $userId2],
        ], $trace->stages);
    }

    #[Test]
    public function stage_durations_are_measured_from_previous_stage(): void
    {
        // -- Arrange
        $trace = self::trace();

        // -- Act
        usleep(10_000);
        $trace->recordRawResult([]);
        $trace->recordDecodedResult([]);
        usleep(20_000);
        $trace->recordTransformedResult([]);
        $trace->recordDenormalizedResult([]);

        // -- Assert
        $durations = $trace->stageDurationsInNanoseconds;
        self::assertSame([
            ProfilingTrace::STAGE_RAW,
            ProfilingTrace::STAGE_DECODED,
            ProfilingTrace::STAGE_TRANSFORMED,
            ProfilingTrace::STAGE_DENORMALIZED,
        ], array_keys($durations));
        self::assertDurationBetween(10_000_000, 20_000_000, $durations[ProfilingTrace::STAGE_RAW]);
        self::assertDurationBetween(0, 5_000_000, $durations[ProfilingTrace::STAGE_DECODED]);
        self::assertDurationBetween(20_000_000, 30_000_000, $durations[ProfilingTrace::STAGE_TRANSFORMED]);
        self::assertDurationBetween(0, 5_000_000, $durations[ProfilingTrace::STAGE_DENORMALIZED]);
    }

    /**
     * The time between the items (when the generator is paused) must not be part of the duration.
     */
    #[Test]
    public function denormalized_item_durations_are_summed_up(): void
    {
        // -- Arrange
        $trace = self::trace();

        // -- Act
        usleep(20_000);
        $trace->startItemDenormalization();
        usleep(10_000);
        $trace->addDenormalizedItem(UserId::generateRandom());
        usleep(20_000);
        $trace->startItemDenormalization();
        usleep(10_000);
        $trace->addDenormalizedItem(UserId::generateRandom());

        // -- Assert
        self::assertDurationBetween(
            20_000_000,
            30_000_000,
            $trace->stageDurationsInNanoseconds[ProfilingTrace::STAGE_DENORMALIZED],
        );
    }

    #[Test]
    public function record_exception_works(): void
    {
        // -- Arrange
        $trace = self::trace();
        $exception = new \RuntimeException('Failed');

        // -- Act
        $trace->recordException($exception);

        // -- Assert
        self::assertSame($exception, $trace->exception);
    }

    private static function assertDurationBetween(int $minimum, int $maximum, int $duration): void
    {
        self::assertGreaterThanOrEqual($minimum, $duration);
        self::assertLessThan($maximum, $duration);
    }

    private static function trace(): ProfilingTrace
    {
        return new ProfilingTrace(
            method: 'DeserializingConnection::findOne',
            sql: 'SELECT :id',
            runnableSql: "SELECT '1'",
            runnableSqlError: null,
            configuration: ['class' => UserId::class],
            caller: ['file' => __FILE__, 'line' => 10],
        );
    }
}
