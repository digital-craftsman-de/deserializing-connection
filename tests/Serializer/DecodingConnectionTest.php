<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Serializer;

use DigitalCraftsman\DeserializingConnection\Test\ConnectionTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(DecodingConnection::class)]
final class DecodingConnectionTest extends ConnectionTestCase
{
    private DecodingConnection $decodingConnection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->decodingConnection = new DecodingConnection(
            connection: $this->connection,
        );
    }

    #[Test]
    #[DataProvider('fetchAssociativeDataProvider')]
    public function fetch_associative_works(
        ?array $expectedResult,
        string $sql,
        array $parameters,
        array $decoderTypes,
    ): void {
        // -- Act & Assert
        try {
            $result = $this->decodingConnection->fetchAssociative(
                sql: $sql,
                parameters: $parameters,
                decoderTypes: $decoderTypes,
            );
            self::assertSame($expectedResult, $result);
        } catch (\Throwable $exception) {
            $result = $exception::class;
            self::assertSame($expectedResult, $result);
        }
    }

    /**
     * @return array<string, array{
     *     expectedResult: array | null,
     *     sql: string,
     *     parameters: array,
     *     decoderTypes: array<string, DTO\DecoderType>,
     * }>
     */
    public static function fetchAssociativeDataProvider(): array
    {
        return [
            'simple row without decoder types' => [
                'expectedResult' => [
                    'userId' => '8c4b339b-75f4-499d-bf3a-56547b212aae',
                    'name' => 'John Doe',
                ],
                'sql' => <<<'SQL'
                    SELECT '8c4b339b-75f4-499d-bf3a-56547b212aae' AS "userId", 'John Doe' AS name
                    SQL,
                'parameters' => [],
                'decoderTypes' => [],
            ],
            'row with json decoding and parameter' => [
                'expectedResult' => [
                    'userId' => '8c4b339b-75f4-499d-bf3a-56547b212aae',
                    'name' => 'John Doe',
                    'accessibleProjects' => [
                        '05f620c2-ea64-4012-816f-884310f69dd0',
                        '91f47435-208d-4344-990b-ae17bd4b13fa',
                    ],
                ],
                'sql' => <<<'SQL'
                    SELECT
                        '8c4b339b-75f4-499d-bf3a-56547b212aae' AS "userId",
                        'John Doe' AS name,
                        '["05f620c2-ea64-4012-816f-884310f69dd0", "91f47435-208d-4344-990b-ae17bd4b13fa"]' AS "accessibleProjects"
                    WHERE '8c4b339b-75f4-499d-bf3a-56547b212aae' = :userId
                    SQL,
                'parameters' => [
                    'userId' => '8c4b339b-75f4-499d-bf3a-56547b212aae',
                ],
                'decoderTypes' => [
                    'accessibleProjects' => DTO\DecoderType::JSON,
                ],
            ],
            'no rows' => [
                'expectedResult' => null,
                'sql' => <<<'SQL'
                    WITH empty_table AS (
                        SELECT 1
                        WHERE false
                    )
                    SELECT *
                    FROM empty_table
                    SQL,
                'parameters' => [],
                'decoderTypes' => [],
            ],
        ];
    }

    #[Test]
    #[DataProvider('fetchBoolDataProvider')]
    public function fetch_bool_works(
        bool | string $expectedResult,
        string $sql,
    ): void {
        // -- Act & Assert
        try {
            $result = $this->decodingConnection->fetchBool($sql);
            self::assertSame($expectedResult, $result);
        } catch (\Throwable $exception) {
            $result = $exception::class;
            self::assertSame($expectedResult, $result);
        }
    }

    /**
     * @return array<string, array{
     *     expectedResult: bool | string,
     *     sql: string,
     * }>
     */
    public static function fetchBoolDataProvider(): array
    {
        return [
            'true' => [
                'expectedResult' => true,
                'sql' => <<<'SQL'
                    SELECT true
                    SQL,
            ],
            'false' => [
                'expectedResult' => false,
                'sql' => <<<'SQL'
                    SELECT false
                    SQL,
            ],
            'no boolean' => [
                'expectedResult' => Exception\QueryDidNotReturnABoolean::class,
                'sql' => <<<'SQL'
                    SELECT 'bla'
                    SQL,
            ],
            'no result' => [
                'expectedResult' => Exception\QueryDidNotReturnExactlyOneResult::class,
                'sql' => <<<'SQL'
                    WITH empty_table AS (
                        SELECT 1
                        WHERE false
                    )
                    SELECT *
                    FROM empty_table
                    SQL,
            ],
        ];
    }
}
