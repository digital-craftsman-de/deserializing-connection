<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Serializer;

use DigitalCraftsman\DeserializingConnection\Profiler\ProfilingTrace;
use DigitalCraftsman\DeserializingConnection\Profiler\ProfilingTracer;
use DigitalCraftsman\DeserializingConnection\Profiler\RunnableQueryRenderer;
use DigitalCraftsman\DeserializingConnection\Profiler\Trace;
use DigitalCraftsman\DeserializingConnection\Test\ConnectionTestCase;
use Doctrine\DBAL\Exception\TableNotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(DecodingConnection::class)]
final class DecodingConnectionTracingTest extends ConnectionTestCase
{
    private ProfilingTracer $tracer;
    private DecodingConnection $decodingConnection;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->tracer = new ProfilingTracer(new RunnableQueryRenderer($this->connection));
        $this->decodingConnection = new DecodingConnection($this->connection, $this->tracer);
    }

    #[Test]
    public function fetch_one_is_traced(): void
    {
        // -- Act
        (void) $this->decodingConnection->fetchOne(
            sql: 'SELECT :value',
            parameters: [
                'value' => '15',
            ],
            decoderType: DTO\DecoderType::INT,
        );

        // -- Assert
        $trace = $this->onlyTrace();
        self::assertSame(DecodingConnection::class.'::fetchOne', $trace->method);
        self::assertSame('SELECT :value', $trace->sql);
        self::assertSame("SELECT '15'", $trace->runnableSql);
        self::assertSame(['decoderType' => DTO\DecoderType::INT], $trace->configuration);
        self::assertSame([
            ProfilingTrace::STAGE_RAW => ['15'],
            ProfilingTrace::STAGE_DECODED => 15,
        ], $trace->stages);
        self::assertNull($trace->exception);
    }

    #[Test]
    public function fetch_one_without_decoder_type_is_traced(): void
    {
        // -- Act
        (void) $this->decodingConnection->fetchOne('SELECT 15');

        // -- Assert
        $trace = $this->onlyTrace();
        self::assertSame([
            ProfilingTrace::STAGE_RAW => [15],
            ProfilingTrace::STAGE_DECODED => 15,
        ], $trace->stages);
    }

    #[Test]
    public function fetch_one_without_result_is_traced(): void
    {
        // -- Act
        (void) $this->decodingConnection->fetchOne('SELECT 1 WHERE false');

        // -- Assert
        $trace = $this->onlyTrace();
        self::assertSame([
            ProfilingTrace::STAGE_RAW => [],
        ], $trace->stages);
    }

    #[Test]
    public function fetch_associative_is_traced(): void
    {
        // -- Act
        (void) $this->decodingConnection->fetchAssociative(
            sql: <<<'SQL'
                SELECT '[1, 2]' AS ids
                SQL,
            decoderTypes: [
                'ids' => DTO\DecoderType::JSON,
            ],
        );

        // -- Assert
        $trace = $this->onlyTrace();
        self::assertSame(DecodingConnection::class.'::fetchAssociative', $trace->method);
        self::assertSame(['decoderTypes' => ['ids' => DTO\DecoderType::JSON]], $trace->configuration);
        self::assertSame([
            ProfilingTrace::STAGE_RAW => ['ids' => '[1, 2]'],
            ProfilingTrace::STAGE_DECODED => ['ids' => [1, 2]],
        ], $trace->stages);
    }

    #[Test]
    public function fetch_associative_without_result_is_traced(): void
    {
        // -- Act
        (void) $this->decodingConnection->fetchAssociative('SELECT 1 WHERE false');

        // -- Assert
        $trace = $this->onlyTrace();
        self::assertSame([
            ProfilingTrace::STAGE_RAW => false,
        ], $trace->stages);
    }

    #[Test]
    public function fetch_all_associative_is_traced(): void
    {
        // -- Act
        (void) $this->decodingConnection->fetchAllAssociative(
            sql: <<<'SQL'
                SELECT *
                FROM (VALUES ('a', '[1]'), ('b', '[2]')) AS t(key, ids)
                SQL,
            decoderTypes: [
                'ids' => DTO\DecoderType::JSON,
            ],
        );

        // -- Assert
        $trace = $this->onlyTrace();
        self::assertSame(DecodingConnection::class.'::fetchAllAssociative', $trace->method);
        self::assertSame([
            'decoderTypes' => ['ids' => DTO\DecoderType::JSON],
            'indexedBy' => null,
        ], $trace->configuration);
        self::assertSame([
            ProfilingTrace::STAGE_RAW => [
                ['key' => 'a', 'ids' => '[1]'],
                ['key' => 'b', 'ids' => '[2]'],
            ],
            ProfilingTrace::STAGE_DECODED => [
                ['key' => 'a', 'ids' => [1]],
                ['key' => 'b', 'ids' => [2]],
            ],
        ], $trace->stages);
    }

    #[Test]
    public function fetch_all_associative_with_index_is_traced(): void
    {
        // -- Act
        (void) $this->decodingConnection->fetchAllAssociative(
            sql: <<<'SQL'
                SELECT *
                FROM (VALUES ('a', '[1]'), ('b', '[2]')) AS t(key, ids)
                SQL,
            decoderTypes: [
                'ids' => DTO\DecoderType::JSON,
            ],
            indexedBy: 'key',
        );

        // -- Assert
        $trace = $this->onlyTrace();
        self::assertSame([
            'decoderTypes' => ['ids' => DTO\DecoderType::JSON],
            'indexedBy' => 'key',
        ], $trace->configuration);
        self::assertSame([
            ProfilingTrace::STAGE_RAW => [
                ['key' => 'a', 'ids' => '[1]'],
                ['key' => 'b', 'ids' => '[2]'],
            ],
            ProfilingTrace::STAGE_DECODED => [
                'a' => ['key' => 'a', 'ids' => [1]],
                'b' => ['key' => 'b', 'ids' => [2]],
            ],
        ], $trace->stages);
    }

    #[Test]
    public function fetch_first_column_is_traced(): void
    {
        // -- Act
        (void) $this->decodingConnection->fetchFirstColumn(
            sql: <<<'SQL'
                SELECT *
                FROM (VALUES ('1'), ('2')) AS t(number)
                SQL,
            decoderType: DTO\DecoderType::INT,
        );

        // -- Assert
        $trace = $this->onlyTrace();
        self::assertSame(DecodingConnection::class.'::fetchFirstColumn', $trace->method);
        self::assertSame(['decoderType' => DTO\DecoderType::INT], $trace->configuration);
        self::assertSame([
            ProfilingTrace::STAGE_RAW => ['1', '2'],
            ProfilingTrace::STAGE_DECODED => [1, 2],
        ], $trace->stages);
    }

    #[Test]
    public function fetch_first_column_without_decoder_type_is_traced(): void
    {
        // -- Act
        (void) $this->decodingConnection->fetchFirstColumn(
            sql: <<<'SQL'
                SELECT *
                FROM (VALUES ('1'), ('2')) AS t(number)
                SQL,
        );

        // -- Assert
        $trace = $this->onlyTrace();
        self::assertSame([
            ProfilingTrace::STAGE_RAW => ['1', '2'],
            ProfilingTrace::STAGE_DECODED => ['1', '2'],
        ], $trace->stages);
    }

    #[Test]
    public function fetch_int_is_traced(): void
    {
        // -- Act
        (void) $this->decodingConnection->fetchInt('SELECT 5');

        // -- Assert
        $trace = $this->onlyTrace();
        self::assertSame(DecodingConnection::class.'::fetchInt', $trace->method);
        self::assertSame([], $trace->configuration);
        self::assertSame([
            ProfilingTrace::STAGE_RAW => [5],
        ], $trace->stages);
    }

    #[Test]
    public function fetch_bool_is_traced(): void
    {
        // -- Act
        (void) $this->decodingConnection->fetchBool('SELECT true');

        // -- Assert
        $trace = $this->onlyTrace();
        self::assertSame(DecodingConnection::class.'::fetchBool', $trace->method);
        self::assertSame([], $trace->configuration);
        self::assertSame([
            ProfilingTrace::STAGE_RAW => [true],
        ], $trace->stages);
    }

    /**
     * Used by the DeserializingConnection, so that all steps are recorded in a single trace.
     *
     * @param \Closure(DecodingConnection, Trace): mixed $usage
     */
    #[Test]
    #[DataProvider('existingTraceDataProvider')]
    public function existing_trace_is_used(\Closure $usage): void
    {
        // -- Arrange
        $existingTrace = new ProfilingTrace(
            method: DeserializingConnection::class.'::findOne',
            sql: 'SELECT 1 AS number',
            runnableSql: 'SELECT 1 AS number',
            runnableSqlError: null,
            configuration: [],
            caller: null,
        );

        // -- Act
        $usage($this->decodingConnection, $existingTrace);

        // -- Assert
        self::assertSame([], $this->tracer->traces());
        self::assertArrayHasKey(ProfilingTrace::STAGE_RAW, $existingTrace->stages);
    }

    /**
     * @return array<string, array{
     *   0: \Closure(DecodingConnection, Trace): mixed,
     * }>
     */
    public static function existingTraceDataProvider(): array
    {
        return [
            'fetch one' => [
                static fn (DecodingConnection $connection, Trace $trace): mixed => $connection->fetchOne('SELECT 1 AS number', trace: $trace),
            ],
            'fetch associative' => [
                static fn (DecodingConnection $connection, Trace $trace): mixed => $connection->fetchAssociative('SELECT 1 AS number', trace: $trace),
            ],
            'fetch all associative' => [
                static fn (DecodingConnection $connection, Trace $trace): mixed => $connection->fetchAllAssociative('SELECT 1 AS number', trace: $trace),
            ],
        ];
    }

    /**
     * @param \Closure(DecodingConnection): mixed $usage
     * @param class-string<\Throwable>            $expectedException
     */
    #[Test]
    #[DataProvider('failureDataProvider')]
    public function failure_is_traced(
        \Closure $usage,
        string $expectedException,
    ): void {
        // -- Act
        $thrownException = null;
        try {
            $usage($this->decodingConnection);
        } catch (\Throwable $exception) {
            $thrownException = $exception;
        }

        // -- Assert
        self::assertInstanceOf($expectedException, $thrownException);

        $trace = $this->onlyTrace();
        self::assertSame($thrownException, $trace->exception);
    }

    /**
     * @return array<string, array{
     *   0: \Closure(DecodingConnection): mixed,
     *   1: class-string<\Throwable>,
     * }>
     */
    public static function failureDataProvider(): array
    {
        return [
            'fetch one' => [
                static fn (DecodingConnection $connection): mixed => $connection->fetchOne('SELECT * FROM missing_table'),
                TableNotFoundException::class,
            ],
            'fetch associative' => [
                static fn (DecodingConnection $connection): mixed => $connection->fetchAssociative('SELECT * FROM missing_table'),
                TableNotFoundException::class,
            ],
            'fetch all associative' => [
                static fn (DecodingConnection $connection): mixed => $connection->fetchAllAssociative('SELECT * FROM missing_table'),
                TableNotFoundException::class,
            ],
            'fetch first column' => [
                static fn (DecodingConnection $connection): mixed => $connection->fetchFirstColumn('SELECT * FROM missing_table'),
                TableNotFoundException::class,
            ],
            'fetch int' => [
                static fn (DecodingConnection $connection): mixed => $connection->fetchInt('SELECT true'),
                Exception\QueryDidNotReturnAnInt::class,
            ],
            'fetch bool' => [
                static fn (DecodingConnection $connection): mixed => $connection->fetchBool('SELECT 1'),
                Exception\QueryDidNotReturnABoolean::class,
            ],
        ];
    }

    private function onlyTrace(): ProfilingTrace
    {
        $traces = $this->tracer->traces();
        self::assertCount(1, $traces);

        return $traces[0];
    }
}
