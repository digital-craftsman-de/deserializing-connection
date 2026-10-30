<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Serializer;

use DigitalCraftsman\DeserializingConnection\Profiler\ProfilingTrace;
use DigitalCraftsman\DeserializingConnection\Profiler\ProfilingTracer;
use DigitalCraftsman\DeserializingConnection\Profiler\RunnableQueryRenderer;
use DigitalCraftsman\DeserializingConnection\Test\ConnectionTestCase;
use DigitalCraftsman\DeserializingConnection\Test\DTO\Company;
use DigitalCraftsman\DeserializingConnection\Test\DTO\Duration;
use DigitalCraftsman\DeserializingConnection\Test\ValueObject\CompanyId;
use DigitalCraftsman\SelfAwareNormalizers\Serializer\ArrayNormalizableNormalizer;
use DigitalCraftsman\SelfAwareNormalizers\Serializer\IntNormalizableNormalizer;
use DigitalCraftsman\SelfAwareNormalizers\Serializer\StringNormalizableNormalizer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Serializer;

#[CoversClass(DeserializingConnection::class)]
final class DeserializingConnectionTracingTest extends ConnectionTestCase
{
    private const string COMPANIES_SQL = <<<'SQL'
        SELECT *
        FROM (
            VALUES
                ('3a3fe751-b621-4baa-a0f1-41b660ca877b', 'Stark Industries'),
                ('8d6d4c4b-5a5b-4d55-9d3b-3e1c4d5e6f70', 'Wayne Enterprises')
        ) AS companies("companyId", name)
        WHERE name LIKE :pattern
        SQL;

    private ProfilingTracer $tracer;
    private DeserializingConnection $deserializingConnection;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->tracer = new ProfilingTracer(new RunnableQueryRenderer($this->connection));

        $typedDenormalizer = new TypedDenormalizer(
            new Serializer(
                normalizers: [
                    new StringNormalizableNormalizer(),
                    new ArrayNormalizableNormalizer(),
                    new IntNormalizableNormalizer(),
                    new ArrayDenormalizer(),
                ],
            ),
        );

        $this->deserializingConnection = new DeserializingConnection(
            decodingConnection: new DecodingConnection($this->connection, $this->tracer),
            typedDenormalizer: $typedDenormalizer,
            resultTransformerRunner: new ResultTransformerRunner($typedDenormalizer),
            tracer: $this->tracer,
        );
    }

    #[Test]
    public function find_one_is_traced(): void
    {
        // -- Arrange
        $resultTransformers = self::nameToUpperCaseTransformers();

        // -- Act
        $this->deserializingConnection->findOne(
            sql: self::COMPANIES_SQL,
            class: Company::class,
            parameters: [
                'pattern' => 'Stark%',
            ],
            resultTransformers: $resultTransformers,
        );

        // -- Assert
        $trace = $this->onlyTrace();
        self::assertSame(DeserializingConnection::class.'::findOne', $trace->method);
        self::assertSame(self::COMPANIES_SQL, $trace->sql);
        self::assertStringContainsString("WHERE name LIKE 'Stark%'", (string) $trace->runnableSql);
        self::assertSame([
            'class' => Company::class,
            'decoderTypes' => [],
            'resultTransformers' => $resultTransformers,
        ], $trace->configuration);
        self::assertEquals([
            ProfilingTrace::STAGE_RAW => self::starkIndustriesRow(),
            ProfilingTrace::STAGE_DECODED => self::starkIndustriesRow(),
            ProfilingTrace::STAGE_TRANSFORMED => self::starkIndustriesRow('STARK INDUSTRIES'),
            ProfilingTrace::STAGE_DENORMALIZED => self::starkIndustries('STARK INDUSTRIES'),
        ], $trace->stages);
        self::assertNull($trace->exception);
    }

    #[Test]
    public function find_one_without_result_is_traced(): void
    {
        // -- Act
        $this->deserializingConnection->findOne(
            sql: self::COMPANIES_SQL,
            class: Company::class,
            parameters: [
                'pattern' => 'Missing%',
            ],
        );

        // -- Assert
        $trace = $this->onlyTrace();
        self::assertSame([
            ProfilingTrace::STAGE_RAW => false,
        ], $trace->stages);
        self::assertNull($trace->exception);
    }

    #[Test]
    public function get_one_is_traced(): void
    {
        // -- Arrange
        $resultTransformers = self::nameToUpperCaseTransformers();

        // -- Act
        $this->deserializingConnection->getOne(
            sql: self::COMPANIES_SQL,
            class: Company::class,
            parameters: [
                'pattern' => 'Stark%',
            ],
            resultTransformers: $resultTransformers,
        );

        // -- Assert
        $trace = $this->onlyTrace();
        self::assertSame(DeserializingConnection::class.'::getOne', $trace->method);
        self::assertSame([
            'class' => Company::class,
            'decoderTypes' => [],
            'resultTransformers' => $resultTransformers,
        ], $trace->configuration);
        self::assertEquals([
            ProfilingTrace::STAGE_RAW => self::starkIndustriesRow(),
            ProfilingTrace::STAGE_DECODED => self::starkIndustriesRow(),
            ProfilingTrace::STAGE_TRANSFORMED => self::starkIndustriesRow('STARK INDUSTRIES'),
            ProfilingTrace::STAGE_DENORMALIZED => self::starkIndustries('STARK INDUSTRIES'),
        ], $trace->stages);
    }

    #[Test]
    public function find_one_from_single_value_is_traced(): void
    {
        // -- Arrange
        $resultTransformer = self::plusFiveTransformer();

        // -- Act
        $this->deserializingConnection->findOneFromSingleValue(
            sql: "SELECT '15'",
            class: Duration::class,
            decoderType: DTO\DecoderType::INT,
            resultTransformer: $resultTransformer,
        );

        // -- Assert
        $trace = $this->onlyTrace();
        self::assertSame(DeserializingConnection::class.'::findOneFromSingleValue', $trace->method);
        self::assertSame([
            'class' => Duration::class,
            'decoderType' => DTO\DecoderType::INT,
            'resultTransformer' => $resultTransformer,
        ], $trace->configuration);
        self::assertEquals([
            ProfilingTrace::STAGE_RAW => ['15'],
            ProfilingTrace::STAGE_DECODED => 15,
            ProfilingTrace::STAGE_TRANSFORMED => 20,
            ProfilingTrace::STAGE_DENORMALIZED => new Duration(20),
        ], $trace->stages);
    }

    #[Test]
    public function find_one_from_single_value_without_result_is_traced(): void
    {
        // -- Act
        $this->deserializingConnection->findOneFromSingleValue(
            sql: 'SELECT 1 WHERE false',
            class: Duration::class,
        );

        // -- Assert
        $trace = $this->onlyTrace();
        self::assertSame([
            ProfilingTrace::STAGE_RAW => [],
        ], $trace->stages);
        self::assertNull($trace->exception);
    }

    #[Test]
    public function get_one_from_single_value_is_traced(): void
    {
        // -- Arrange
        $resultTransformer = self::plusFiveTransformer();

        // -- Act
        $this->deserializingConnection->getOneFromSingleValue(
            sql: "SELECT '15'",
            class: Duration::class,
            decoderType: DTO\DecoderType::INT,
            resultTransformer: $resultTransformer,
        );

        // -- Assert
        $trace = $this->onlyTrace();
        self::assertSame(DeserializingConnection::class.'::getOneFromSingleValue', $trace->method);
        self::assertSame([
            'class' => Duration::class,
            'decoderType' => DTO\DecoderType::INT,
            'resultTransformer' => $resultTransformer,
        ], $trace->configuration);
        self::assertEquals([
            ProfilingTrace::STAGE_RAW => ['15'],
            ProfilingTrace::STAGE_DECODED => 15,
            ProfilingTrace::STAGE_TRANSFORMED => 20,
            ProfilingTrace::STAGE_DENORMALIZED => new Duration(20),
        ], $trace->stages);
    }

    #[Test]
    public function find_array_is_traced(): void
    {
        // -- Arrange
        $resultTransformers = self::nameToUpperCaseTransformers();

        // -- Act
        $this->deserializingConnection->findArray(
            sql: self::COMPANIES_SQL,
            class: Company::class,
            parameters: [
                'pattern' => '%',
            ],
            resultTransformers: $resultTransformers,
        );

        // -- Assert
        $trace = $this->onlyTrace();
        self::assertSame(DeserializingConnection::class.'::findArray', $trace->method);
        self::assertSame([
            'class' => Company::class,
            'decoderTypes' => [],
            'resultTransformers' => $resultTransformers,
        ], $trace->configuration);
        self::assertEquals([
            ProfilingTrace::STAGE_RAW => [
                self::starkIndustriesRow(),
                self::wayneEnterprisesRow(),
            ],
            ProfilingTrace::STAGE_DECODED => [
                self::starkIndustriesRow(),
                self::wayneEnterprisesRow(),
            ],
            ProfilingTrace::STAGE_TRANSFORMED => [
                self::starkIndustriesRow('STARK INDUSTRIES'),
                self::wayneEnterprisesRow('WAYNE ENTERPRISES'),
            ],
            ProfilingTrace::STAGE_DENORMALIZED => [
                self::starkIndustries('STARK INDUSTRIES'),
                self::wayneEnterprises('WAYNE ENTERPRISES'),
            ],
        ], $trace->stages);
    }

    #[Test]
    public function find_array_with_index_is_traced(): void
    {
        // -- Act
        $this->deserializingConnection->findArray(
            sql: self::COMPANIES_SQL,
            class: Company::class,
            parameters: [
                'pattern' => '%',
            ],
            indexedBy: static fn (Company $company): string => (string) $company->companyId,
        );

        // -- Assert
        $trace = $this->onlyTrace();
        self::assertEquals([
            '3a3fe751-b621-4baa-a0f1-41b660ca877b' => self::starkIndustries(),
            '8d6d4c4b-5a5b-4d55-9d3b-3e1c4d5e6f70' => self::wayneEnterprises(),
        ], $trace->stages[ProfilingTrace::STAGE_DENORMALIZED]);
    }

    #[Test]
    public function find_generator_is_traced(): void
    {
        // -- Arrange
        $resultTransformers = self::nameToUpperCaseTransformers();

        // -- Act
        $companies = $this->deserializingConnection->findGenerator(
            sql: self::COMPANIES_SQL,
            class: Company::class,
            parameters: [
                'pattern' => '%',
            ],
            resultTransformers: $resultTransformers,
        );
        iterator_to_array($companies);

        // -- Assert
        $trace = $this->onlyTrace();
        self::assertSame(DeserializingConnection::class.'::findGenerator', $trace->method);
        self::assertSame([
            'class' => Company::class,
            'decoderTypes' => [],
            'resultTransformers' => $resultTransformers,
        ], $trace->configuration);
        self::assertEquals([
            ProfilingTrace::STAGE_RAW => [
                self::starkIndustriesRow(),
                self::wayneEnterprisesRow(),
            ],
            ProfilingTrace::STAGE_DECODED => [
                self::starkIndustriesRow(),
                self::wayneEnterprisesRow(),
            ],
            ProfilingTrace::STAGE_TRANSFORMED => [
                self::starkIndustriesRow('STARK INDUSTRIES'),
                self::wayneEnterprisesRow('WAYNE ENTERPRISES'),
            ],
            ProfilingTrace::STAGE_DENORMALIZED => [
                self::starkIndustries('STARK INDUSTRIES'),
                self::wayneEnterprises('WAYNE ENTERPRISES'),
            ],
        ], $trace->stages);
    }

    /**
     * @param \Closure(DeserializingConnection): mixed $usage
     * @param class-string<\Throwable>                 $expectedException
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
            $usage($this->deserializingConnection);
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
     *   0: \Closure(DeserializingConnection): mixed,
     *   1: class-string<\Throwable>,
     * }>
     */
    public static function failureDataProvider(): array
    {
        $invalidResultTransformers = [
            DTO\ResultTransformer::toTransform(
                key: 'missing',
                denormalizeResultToClass: null,
                transformer: static fn (string $name): string => $name,
                isTransformedResultNormalized: false,
            ),
        ];
        $renamingResultTransformer = DTO\ResultTransformer::toTransformAndRename(
            key: 'key',
            denormalizeResultToClass: null,
            transformer: static fn (int $value): int => $value,
            isTransformedResultNormalized: false,
            renameTo: 'renamedKey',
        );

        return [
            'find one' => [
                static fn (DeserializingConnection $connection): mixed => $connection->findOne(
                    sql: "SELECT 'Stark Industries' AS name",
                    class: Company::class,
                    resultTransformers: $invalidResultTransformers,
                ),
                Exception\ResultTransformerKeyNotFound::class,
            ],
            'get one' => [
                static fn (DeserializingConnection $connection): mixed => $connection->getOne(
                    sql: 'SELECT 1 WHERE false',
                    class: Company::class,
                ),
                Exception\ElementNotFound::class,
            ],
            'find one from single value' => [
                static fn (DeserializingConnection $connection): mixed => $connection->findOneFromSingleValue(
                    sql: 'SELECT 1',
                    class: Duration::class,
                    resultTransformer: $renamingResultTransformer,
                ),
                Exception\SingleValueTransformationMustNotContainRenaming::class,
            ],
            'get one from single value' => [
                static fn (DeserializingConnection $connection): mixed => $connection->getOneFromSingleValue(
                    sql: 'SELECT 1 WHERE false',
                    class: Duration::class,
                ),
                Exception\ElementNotFound::class,
            ],
            'find array' => [
                static fn (DeserializingConnection $connection): mixed => $connection->findArray(
                    sql: "SELECT 'Stark Industries' AS name",
                    class: Company::class,
                    resultTransformers: $invalidResultTransformers,
                ),
                Exception\ResultTransformerKeyNotFound::class,
            ],
            'find generator' => [
                static fn (DeserializingConnection $connection): mixed => iterator_to_array($connection->findGenerator(
                    sql: "SELECT 'Stark Industries' AS name",
                    class: Company::class,
                    resultTransformers: $invalidResultTransformers,
                )),
                Exception\ResultTransformerKeyNotFound::class,
            ],
        ];
    }

    private function onlyTrace(): ProfilingTrace
    {
        $traces = $this->tracer->traces();
        self::assertCount(1, $traces);

        return $traces[0];
    }

    /**
     * @return list<DTO\ResultTransformer>
     */
    private static function nameToUpperCaseTransformers(): array
    {
        return [
            DTO\ResultTransformer::toTransform(
                key: 'name',
                denormalizeResultToClass: null,
                transformer: static fn (string $name): string => strtoupper($name),
                isTransformedResultNormalized: false,
            ),
        ];
    }

    private static function plusFiveTransformer(): DTO\ResultTransformer
    {
        return DTO\ResultTransformer::toTransform(
            key: 'key',
            denormalizeResultToClass: null,
            transformer: static fn (int $value): int => $value + 5,
            isTransformedResultNormalized: false,
        );
    }

    /**
     * @return array{companyId: string, name: string}
     */
    private static function starkIndustriesRow(string $name = 'Stark Industries'): array
    {
        return [
            'companyId' => '3a3fe751-b621-4baa-a0f1-41b660ca877b',
            'name' => $name,
        ];
    }

    /**
     * @return array{companyId: string, name: string}
     */
    private static function wayneEnterprisesRow(string $name = 'Wayne Enterprises'): array
    {
        return [
            'companyId' => '8d6d4c4b-5a5b-4d55-9d3b-3e1c4d5e6f70',
            'name' => $name,
        ];
    }

    private static function starkIndustries(string $name = 'Stark Industries'): Company
    {
        return new Company(
            companyId: CompanyId::fromString('3a3fe751-b621-4baa-a0f1-41b660ca877b'),
            name: $name,
        );
    }

    private static function wayneEnterprises(string $name = 'Wayne Enterprises'): Company
    {
        return new Company(
            companyId: CompanyId::fromString('8d6d4c4b-5a5b-4d55-9d3b-3e1c4d5e6f70'),
            name: $name,
        );
    }
}
