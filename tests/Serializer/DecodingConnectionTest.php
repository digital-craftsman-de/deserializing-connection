<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Serializer;

use DigitalCraftsman\DeserializingConnection\Test\ConnectionTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(DecodingConnection::class)]
#[CoversClass(Exception\QueryDidNotReturnExactlyOneResult::class)]
#[CoversClass(Exception\QueryDidNotReturnAnInt::class)]
#[CoversClass(Exception\QueryDidNotReturnABoolean::class)]
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
    #[DataProvider('fetchOneDataProvider')]
    public function fetch_one_works(
        mixed $expectedResult,
        string $sql,
        array $parameters,
        array $parameterTypes,
        ?DTO\DecoderType $decoderType,
    ): void {
        // -- Act & Assert
        try {
            $result = $this->decodingConnection->fetchOne(
                sql: $sql,
                parameters: $parameters,
                parameterTypes: $parameterTypes,
                decoderType: $decoderType,
            );
            self::assertEquals($expectedResult, $result);
        } catch (\Throwable $exception) {
            $result = $exception::class;
            self::assertSame($expectedResult, $result);
        }
    }

    /**
     * @return array<string, array{
     *     expectedResult: mixed,
     *     sql: string,
     *     parameters: array,
     *     parameterTypes: array,
     *     decoderType: DTO\DecoderType,
     * }>
     */
    public static function fetchOneDataProvider(): array
    {
        return [
            'json value with decoding' => [
                'expectedResult' => [
                    'userId' => '8c4b339b-75f4-499d-bf3a-56547b212aae',
                    'name' => 'John Doe',
                ],
                'sql' => <<<'SQL'
                    SELECT jsonb_build_object(
                        'userId', '8c4b339b-75f4-499d-bf3a-56547b212aae',
                        'name', 'John Doe'
                    )
                    SQL,
                'parameters' => [],
                'parameterTypes' => [],
                'decoderType' => DTO\DecoderType::JSON,
            ],
            'string without decoding' => [
                'expectedResult' => '8c4b339b-75f4-499d-bf3a-56547b212aae',
                'sql' => <<<'SQL'
                    SELECT '8c4b339b-75f4-499d-bf3a-56547b212aae'
                    SQL,
                'parameters' => [],
                'parameterTypes' => [],
                'decoderType' => null,
            ],
            'bool false with decoding' => [
                'expectedResult' => false,
                'sql' => <<<'SQL'
                    SELECT 'false'
                    SQL,
                'parameters' => [],
                'parameterTypes' => [],
                'decoderType' => DTO\DecoderType::BOOL,
            ],
            'bool true with decoding' => [
                'expectedResult' => true,
                'sql' => <<<'SQL'
                    SELECT true
                    SQL,
                'parameters' => [],
                'parameterTypes' => [],
                'decoderType' => DTO\DecoderType::BOOL,
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
                'parameterTypes' => [],
                'decoderType' => null,
            ],
        ];
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
    #[DataProvider('fetchAllAssociativeDataProvider')]
    public function fetch_all_associative_works(
        array $expectedResult,
        string $sql,
        array $parameters,
        array $decoderTypes,
    ): void {
        // -- Act
        $result = $this->decodingConnection->fetchAllAssociative(
            sql: $sql,
            parameters: $parameters,
            decoderTypes: $decoderTypes,
        );

        // -- Assert
        self::assertSame($expectedResult, $result);
    }

    /**
     * @return array<string, array{
     *     expectedResult: array,
     *     sql: string,
     *     parameters: array,
     *     decoderTypes: array<string, DTO\DecoderType>,
     * }>
     */
    public static function fetchAllAssociativeDataProvider(): array
    {
        return [
            'simple row without decoder types' => [
                'expectedResult' => [
                    [
                        'userId' => '8c4b339b-75f4-499d-bf3a-56547b212aae',
                        'name' => 'John Doe',
                    ],
                    [
                        'userId' => '16092d20-c57d-44e0-ac87-3eff8b6bcd1e',
                        'name' => 'John Doe',
                    ],
                ],
                'sql' => <<<'SQL'
                    SELECT
                        user_id AS "userId",
                        name
                    FROM (
                        VALUES
                            ('8c4b339b-75f4-499d-bf3a-56547b212aae', 'John Doe'),
                            ('16092d20-c57d-44e0-ac87-3eff8b6bcd1e', 'John Doe')
                    ) AS users(user_id, name)
                    SQL,
                'parameters' => [],
                'decoderTypes' => [],
            ],
            'row with json decoding and parameter' => [
                'expectedResult' => [
                    [
                        'userId' => '8c4b339b-75f4-499d-bf3a-56547b212aae',
                        'name' => 'John Doe',
                        'accessibleProjects' => [
                            '05f620c2-ea64-4012-816f-884310f69dd0',
                            '91f47435-208d-4344-990b-ae17bd4b13fa',
                        ],
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
                'expectedResult' => [],
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

    #[Test]
    #[DataProvider('fetchIntDataProvider')]
    public function fetch_int_works(
        int | string $expectedResult,
        string $sql,
    ): void {
        // -- Act & Assert
        try {
            $result = $this->decodingConnection->fetchInt($sql);
            self::assertSame($expectedResult, $result);
        } catch (\Throwable $exception) {
            $result = $exception::class;
            self::assertSame($expectedResult, $result);
        }
    }

    /**
     * @return array<string, array{
     *     expectedResult: int | string,
     *     sql: string,
     * }>
     */
    public static function fetchIntDataProvider(): array
    {
        return [
            'int' => [
                'expectedResult' => 5,
                'sql' => <<<'SQL'
                    SELECT 5
                    SQL,
            ],
            'float' => [
                'expectedResult' => Exception\QueryDidNotReturnAnInt::class,
                'sql' => <<<'SQL'
                    SELECT 4.0
                    SQL,
            ],
            'string' => [
                'expectedResult' => Exception\QueryDidNotReturnAnInt::class,
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

    #[Test]
    public function decode_item_works(): void
    {
        // -- Arrange
        $item = [
            'userId' => '8c4b339b-75f4-499d-bf3a-56547b212aae',
            'name' => 'John Doe',

            'int' => '1',

            'nullableInt' => null,
            'nullableIntWithValue' => '2',

            'float' => '3',

            'nullableFloat' => null,
            'nullableFloatWithValue' => '4',

            'json' => '{"userId": "8c4b339b-75f4-499d-bf3a-56547b212aae", "name": "John Doe"}',

            'nullableJson' => null,
            'nullableJsonWithValue' => '{"userId": "8c4b339b-75f4-499d-bf3a-56547b212aae", "name": "John Doe"}',

            'jsonWithEmptyArrayOnNull' => null,
            'jsonWithEmptyArrayOnNullWithValue' => '["fdf7d3f4-7c17-4917-b637-d8baf13f2b07", "b3b3b3b3-7c17-4917-b637-d8baf13f2b07"]',
        ];
        $decoderTypes = [
            'int' => DTO\DecoderType::INT,

            'nullableInt' => DTO\DecoderType::NULLABLE_INT,
            'nullableIntWithValue' => DTO\DecoderType::NULLABLE_INT,

            'float' => DTO\DecoderType::FLOAT,

            'nullableFloat' => DTO\DecoderType::NULLABLE_FLOAT,
            'nullableFloatWithValue' => DTO\DecoderType::NULLABLE_FLOAT,

            'json' => DTO\DecoderType::JSON,

            'nullableJson' => DTO\DecoderType::NULLABLE_JSON,
            'nullableJsonWithValue' => DTO\DecoderType::NULLABLE_JSON,

            'jsonWithEmptyArrayOnNull' => DTO\DecoderType::JSON_WITH_EMPTY_ARRAY_ON_NULL,
            'jsonWithEmptyArrayOnNullWithValue' => DTO\DecoderType::JSON_WITH_EMPTY_ARRAY_ON_NULL,
        ];

        // -- Act
        DecodingConnection::decodeItem(
            item: $item,
            decoderTypes: $decoderTypes,
        );

        // -- Assert
        self::assertSame(
            [
                'userId' => '8c4b339b-75f4-499d-bf3a-56547b212aae',
                'name' => 'John Doe',

                'int' => 1,

                'nullableInt' => null,
                'nullableIntWithValue' => 2,

                'float' => 3.0,

                'nullableFloat' => null,
                'nullableFloatWithValue' => 4.0,

                'json' => [
                    'userId' => '8c4b339b-75f4-499d-bf3a-56547b212aae',
                    'name' => 'John Doe',
                ],

                'nullableJson' => null,
                'nullableJsonWithValue' => [
                    'userId' => '8c4b339b-75f4-499d-bf3a-56547b212aae',
                    'name' => 'John Doe',
                ],

                'jsonWithEmptyArrayOnNull' => [],
                'jsonWithEmptyArrayOnNullWithValue' => [
                    'fdf7d3f4-7c17-4917-b637-d8baf13f2b07',
                    'b3b3b3b3-7c17-4917-b637-d8baf13f2b07',
                ],
            ],
            $item,
        );
    }

    #[Test]
    public function decode_results_works(): void
    {
        // -- Arrange
        $results = [
            [
                'userId' => '8c4b339b-75f4-499d-bf3a-56547b212aae',
                'name' => 'John Doe',

                'bool' => 'false',

                'nullableBool' => null,
                'nullableBoolWithValue' => 'false',

                'int' => '1',

                'nullableInt' => null,
                'nullableIntWithValue' => '2',

                'float' => '3',

                'nullableFloat' => null,
                'nullableFloatWithValue' => '4',

                'json' => '{"userId": "8c4b339b-75f4-499d-bf3a-56547b212aae", "name": "John Doe"}',

                'nullableJson' => null,
                'nullableJsonWithValue' => '{"userId": "8c4b339b-75f4-499d-bf3a-56547b212aae", "name": "John Doe"}',

                'jsonWithEmptyArrayOnNull' => null,
                'jsonWithEmptyArrayOnNullWithValue' => '["fdf7d3f4-7c17-4917-b637-d8baf13f2b07", "b3b3b3b3-7c17-4917-b637-d8baf13f2b07"]',
            ],
        ];
        $decoderTypes = [
            'bool' => DTO\DecoderType::BOOL,

            'nullableBool' => DTO\DecoderType::NULLABLE_BOOL,
            'nullableBoolWithValue' => DTO\DecoderType::NULLABLE_BOOL,

            'int' => DTO\DecoderType::INT,

            'nullableInt' => DTO\DecoderType::NULLABLE_INT,
            'nullableIntWithValue' => DTO\DecoderType::NULLABLE_INT,

            'float' => DTO\DecoderType::FLOAT,

            'nullableFloat' => DTO\DecoderType::NULLABLE_FLOAT,
            'nullableFloatWithValue' => DTO\DecoderType::NULLABLE_FLOAT,

            'json' => DTO\DecoderType::JSON,

            'nullableJson' => DTO\DecoderType::NULLABLE_JSON,
            'nullableJsonWithValue' => DTO\DecoderType::NULLABLE_JSON,

            'jsonWithEmptyArrayOnNull' => DTO\DecoderType::JSON_WITH_EMPTY_ARRAY_ON_NULL,
            'jsonWithEmptyArrayOnNullWithValue' => DTO\DecoderType::JSON_WITH_EMPTY_ARRAY_ON_NULL,
        ];

        // -- Act
        DecodingConnection::decodeResults(
            data: $results,
            decoderTypes: $decoderTypes,
        );

        // -- Assert
        self::assertSame(
            [
                [
                    'userId' => '8c4b339b-75f4-499d-bf3a-56547b212aae',
                    'name' => 'John Doe',

                    'bool' => false,

                    'nullableBool' => null,
                    'nullableBoolWithValue' => false,

                    'int' => 1,

                    'nullableInt' => null,
                    'nullableIntWithValue' => 2,

                    'float' => 3.0,

                    'nullableFloat' => null,
                    'nullableFloatWithValue' => 4.0,

                    'json' => [
                        'userId' => '8c4b339b-75f4-499d-bf3a-56547b212aae',
                        'name' => 'John Doe',
                    ],

                    'nullableJson' => null,
                    'nullableJsonWithValue' => [
                        'userId' => '8c4b339b-75f4-499d-bf3a-56547b212aae',
                        'name' => 'John Doe',
                    ],

                    'jsonWithEmptyArrayOnNull' => [],
                    'jsonWithEmptyArrayOnNullWithValue' => [
                        'fdf7d3f4-7c17-4917-b637-d8baf13f2b07',
                        'b3b3b3b3-7c17-4917-b637-d8baf13f2b07',
                    ],
                ],
            ],
            $results,
        );
    }
}
