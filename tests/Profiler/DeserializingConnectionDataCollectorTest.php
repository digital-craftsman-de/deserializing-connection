<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Profiler;

use DigitalCraftsman\DeserializingConnection\Serializer\DTO\DecoderType;
use DigitalCraftsman\DeserializingConnection\Test\ConnectionTestCase;
use DigitalCraftsman\DeserializingConnection\Test\ValueObject\UserId;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\VarDumper\Cloner\Data;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;

#[CoversClass(DeserializingConnectionDataCollector::class)]
final class DeserializingConnectionDataCollectorTest extends ConnectionTestCase
{
    private ProfilingTracer $tracer;
    private DeserializingConnectionDataCollector $dataCollector;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->tracer = new ProfilingTracer(new RunnableQueryRenderer($this->connection));
        $this->dataCollector = new DeserializingConnectionDataCollector($this->tracer, new SqlHighlighter());
    }

    #[Test]
    public function late_collect_works(): void
    {
        // -- Arrange
        $userId = UserId::generateRandom();

        $successfulTrace = $this->tracer->startTrace(
            'DeserializingConnection::findOne',
            'SELECT :id AS id',
            ['id' => (string) $userId],
            [],
            ['class' => UserId::class],
        );
        $successfulTrace->recordRawResult(['id' => (string) $userId]);
        $successfulTrace->recordDenormalizedResult($userId);

        $failedTrace = $this->tracer->startTrace(
            'DecodingConnection::fetchInt',
            'SELECT :id AS id',
            [],
            [],
            [],
        );
        $failedTrace->recordException(new \RuntimeException('Failed'));

        // -- Act
        $this->dataCollector->collect(new Request(), new Response());
        $this->dataCollector->lateCollect();

        // -- Assert
        $traces = $this->dataCollector->getTraces();
        self::assertCount(2, $traces);

        $successfulTraceData = $traces[0];
        self::assertSame('DeserializingConnection::findOne', $successfulTraceData['method']);
        self::assertSame('SELECT :id AS id', $successfulTraceData['sql']);
        self::assertSame(sprintf("SELECT '%s' AS id", (string) $userId), $successfulTraceData['runnableSql']);
        self::assertNull($successfulTraceData['runnableSqlError']);
        self::assertSame(
            new SqlHighlighter()->highlight(sprintf("SELECT '%s' AS id", (string) $userId)),
            $successfulTraceData['highlightedSql'],
        );
        self::assertNull($successfulTraceData['caller']);
        self::assertSame(['class' => UserId::class], $successfulTraceData['configuration']->getValue(true));
        self::assertSame($successfulTrace->stageDurationsInNanoseconds, $successfulTraceData['stageDurationsInNanoseconds']);
        self::assertSame([ProfilingTrace::STAGE_RAW, ProfilingTrace::STAGE_DENORMALIZED], array_keys($successfulTraceData['stages']));
        self::assertSame(['id' => (string) $userId], $successfulTraceData['stages'][ProfilingTrace::STAGE_RAW]->getValue(true));
        self::assertInstanceOf(Data::class, $successfulTraceData['stages'][ProfilingTrace::STAGE_DENORMALIZED]);
        self::assertNull($successfulTraceData['exception']);
        self::assertNull($successfulTraceData['exceptionClass']);
        self::assertNull($successfulTraceData['exceptionMessage']);

        $failedTraceData = $traces[1];
        self::assertSame('DecodingConnection::fetchInt', $failedTraceData['method']);
        self::assertNull($failedTraceData['runnableSql']);
        self::assertSame('The runnable query could not be rendered: Named parameter "id" is missing', $failedTraceData['runnableSqlError']);
        self::assertSame(new SqlHighlighter()->highlight('SELECT :id AS id'), $failedTraceData['highlightedSql']);
        self::assertSame([], $failedTraceData['stageDurationsInNanoseconds']);
        self::assertSame([], $failedTraceData['stages']);
        self::assertInstanceOf(Data::class, $failedTraceData['exception']);
        self::assertSame(\RuntimeException::class, $failedTraceData['exceptionClass']);
        self::assertSame('Failed', $failedTraceData['exceptionMessage']);
    }

    #[Test]
    public function enums_in_configuration_are_shown_as_written_in_code(): void
    {
        // -- Arrange
        $this->tracer->startTrace(
            'DeserializingConnection::findOneFromSingleValue',
            'SELECT 1',
            [],
            [],
            [
                'class' => UserId::class,
                'decoderType' => DecoderType::NULLABLE_JSON,
                'decoderTypes' => [
                    'tags' => DecoderType::JSON,
                ],
            ],
        );

        // -- Act
        $this->dataCollector->lateCollect();

        // -- Assert
        $dumper = new HtmlDumper();
        $dumper->setDumpHeader('');
        $dump = (string) $dumper->dump($this->dataCollector->getTraces()[0]['configuration'], true);

        self::assertStringContainsString(
            sprintf('<span class=sf-dump-str title="64 characters">%s</span>', UserId::class),
            html_entity_decode($dump),
        );
        self::assertStringContainsString(
            sprintf('<span class=sf-dump-const title="%s::NULLABLE_JSON">DecoderType::NULLABLE_JSON</span>', DecoderType::class),
            html_entity_decode($dump),
        );
        self::assertStringContainsString(
            sprintf('<span class=sf-dump-const title="%s::JSON">DecoderType::JSON</span>', DecoderType::class),
            html_entity_decode($dump),
        );
    }

    #[Test]
    public function get_traces_works_without_collected_data(): void
    {
        // -- Act & Assert
        self::assertSame([], $this->dataCollector->getTraces());
    }

    #[Test]
    public function reset_works(): void
    {
        // -- Arrange
        $this->tracer->startTrace('DecodingConnection::fetchInt', 'SELECT 1', [], [], []);
        $this->dataCollector->lateCollect();

        // -- Act
        $this->dataCollector->reset();

        // -- Assert
        self::assertSame([], $this->dataCollector->getTraces());
    }

    #[Test]
    public function name_and_template_are_defined(): void
    {
        // -- Act & Assert
        self::assertSame('deserializing_connection', $this->dataCollector->getName());
        self::assertSame(
            '@DeserializingConnection/Collector/deserializing_connection.html.twig',
            DeserializingConnectionDataCollector::getTemplate(),
        );
    }
}
