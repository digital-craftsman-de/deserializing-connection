<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Profiler;

use DigitalCraftsman\DeserializingConnection\Test\ValueObject\UserId;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(NullTracer::class)]
#[CoversClass(NullTrace::class)]
final class NullTracerTest extends TestCase
{
    #[Test]
    public function start_trace_returns_null_trace(): void
    {
        // -- Act
        $trace = new NullTracer()->startTrace(
            'DeserializingConnection::findOne',
            'SELECT :id AS id',
            ['id' => 5],
            [],
            [],
        );

        // -- Assert
        self::assertInstanceOf(NullTrace::class, $trace);
    }

    #[Test]
    public function null_trace_ignores_all_records(): void
    {
        // -- Arrange
        $trace = new NullTrace();
        $traceBefore = clone $trace;

        // -- Act
        $trace->recordRawResult(['id' => '5']);
        $trace->recordDecodedResult(['id' => 5]);
        $trace->recordTransformedResult(['id' => 5]);
        $trace->recordDenormalizedResult(UserId::generateRandom());
        $trace->startItemDenormalization();
        $trace->addDenormalizedItem(UserId::generateRandom());
        $trace->recordException(new \RuntimeException('Failed'));

        // -- Assert
        self::assertEquals($traceBefore, $trace);
    }
}
