<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Profiler;

use DigitalCraftsman\DeserializingConnection\Serializer\DecodingConnection;
use DigitalCraftsman\DeserializingConnection\Serializer\DeserializingConnection;
use DigitalCraftsman\DeserializingConnection\Serializer\ResultTransformerRunner;
use DigitalCraftsman\DeserializingConnection\Serializer\TypedDenormalizer;
use DigitalCraftsman\DeserializingConnection\Test\ConnectionTestCase;
use DigitalCraftsman\DeserializingConnection\Test\ValueObject\UserId;
use Doctrine\DBAL\ParameterType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Serializer\Serializer;

#[CoversClass(ProfilingTracer::class)]
#[CoversClass(ProfilingTrace::class)]
#[CoversClass(RunnableQueryRenderer::class)]
#[CoversClass(RunnableQueryVisitor::class)]
#[CoversClass(Exception\QueryCouldNotBeRendered::class)]
final class ProfilingTracerTest extends ConnectionTestCase
{
    private ProfilingTracer $tracer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->tracer = new ProfilingTracer(new RunnableQueryRenderer($this->connection));
    }

    #[Test]
    public function start_trace_works(): void
    {
        // -- Act
        $trace = $this->tracer->startTrace(
            'DeserializingConnection::findOne',
            'SELECT :id AS id',
            ['id' => 5],
            ['id' => ParameterType::INTEGER],
            ['decoderTypes' => []],
        );

        // -- Assert
        self::assertInstanceOf(ProfilingTrace::class, $trace);
        self::assertSame('DeserializingConnection::findOne', $trace->method);
        self::assertSame('SELECT :id AS id', $trace->sql);
        self::assertSame("SELECT '5' AS id", $trace->runnableSql);
        self::assertNull($trace->runnableSqlError);
        self::assertSame(['decoderTypes' => []], $trace->configuration);
        self::assertNull($trace->caller);
        self::assertSame([$trace], $this->tracer->traces());
    }

    #[Test]
    public function start_trace_works_when_query_can_not_be_rendered(): void
    {
        // -- Act
        $trace = $this->tracer->startTrace(
            'DeserializingConnection::findOne',
            'SELECT :id AS id',
            [],
            [],
            [],
        );

        // -- Assert
        self::assertInstanceOf(ProfilingTrace::class, $trace);
        self::assertNull($trace->runnableSql);
        self::assertSame('The runnable query could not be rendered: Named parameter "id" is missing', $trace->runnableSqlError);
    }

    #[Test]
    public function start_trace_collects_multiple_traces(): void
    {
        // -- Act
        $trace1 = $this->tracer->startTrace('DecodingConnection::fetchInt', 'SELECT 1', [], [], []);
        $trace2 = $this->tracer->startTrace('DecodingConnection::fetchInt', 'SELECT 2', [], [], []);

        // -- Assert
        self::assertSame([$trace1, $trace2], $this->tracer->traces());
    }

    #[Test]
    public function caller_is_the_location_of_the_usage(): void
    {
        // -- Arrange
        $decodingConnection = new DecodingConnection($this->connection, $this->tracer);

        // -- Act
        $line = __LINE__ + 1;
        (void) $decodingConnection->fetchInt('SELECT 1');

        // -- Assert
        $trace = $this->tracer->traces()[0];
        self::assertInstanceOf(ProfilingTrace::class, $trace);
        self::assertSame(['file' => __FILE__, 'line' => $line], $trace->caller);
    }

    #[Test]
    public function caller_is_the_location_of_the_usage_of_deserializing_connection(): void
    {
        // -- Arrange
        $deserializingConnection = new DeserializingConnection(
            decodingConnection: new DecodingConnection($this->connection, $this->tracer),
            typedDenormalizer: new TypedDenormalizer(new Serializer()),
            resultTransformerRunner: new ResultTransformerRunner(new TypedDenormalizer(new Serializer())),
            tracer: $this->tracer,
        );

        // -- Act
        $line = __LINE__ + 1;
        (void) $deserializingConnection->findOneFromSingleValue('SELECT 1 WHERE false', UserId::class);

        // -- Assert
        $trace = $this->tracer->traces()[0];
        self::assertInstanceOf(ProfilingTrace::class, $trace);
        self::assertSame(['file' => __FILE__, 'line' => $line], $trace->caller);
    }

    #[Test]
    public function caller_is_null_when_called_through_internal_function(): void
    {
        // -- Arrange
        $decodingConnection = new DecodingConnection($this->connection, $this->tracer);

        // -- Act
        array_map($decodingConnection->fetchInt(...), ['SELECT 1']);

        // -- Assert
        $trace = $this->tracer->traces()[0];
        self::assertInstanceOf(ProfilingTrace::class, $trace);
        self::assertNull($trace->caller);
    }

    #[Test]
    public function reset_works(): void
    {
        // -- Arrange
        $this->tracer->startTrace('DecodingConnection::fetchInt', 'SELECT 1', [], [], []);

        // -- Act
        $this->tracer->reset();

        // -- Assert
        self::assertSame([], $this->tracer->traces());
    }
}
