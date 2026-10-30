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
        self::assertNull($trace->queryDurationInNanoseconds);
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
    public function query_duration_is_measured_until_raw_result_is_recorded(): void
    {
        // -- Arrange
        $trace = self::trace();
        usleep(1000);

        // -- Act
        $trace->recordRawResult([]);

        // -- Assert
        self::assertGreaterThanOrEqual(1_000_000, $trace->queryDurationInNanoseconds);
        self::assertLessThan(1_000_000_000, $trace->queryDurationInNanoseconds);
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
