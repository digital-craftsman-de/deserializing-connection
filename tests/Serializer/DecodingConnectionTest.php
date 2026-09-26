<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Serializer;

use DigitalCraftsman\DeserializingConnection\Test\ConnectionTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\ExpectationFailedException;

#[CoversClass(DecodingConnection::class)]
#[CoversClass(Exception\QueryDidNotReturnExactlyOneResult::class)]
#[CoversClass(Exception\QueryDidNotReturnAnInt::class)]
#[CoversClass(Exception\QueryDidNotReturnABoolean::class)]
#[CoversClass(Exception\DecoderTypeKeyLevelIsNotAnArray::class)]
#[CoversClass(DTO\ResultTransformerKey::class)]
#[CoversClass(DTO\Exception\ResultTransformationKeyCanNotStartWithAnArrayIdentifier::class)]
#[CoversClass(DTO\Exception\ResultTransformationKeyCanNotEndWithAnArrayIdentifier::class)]
final class DecodingConnectionTest extends ConnectionTestCase
{
    private DecodingConnection $decodingConnection;

    #[\Override]
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
            if ($exception instanceof ExpectationFailedException) {
                throw $exception;
            }

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
    #[DataProvider('fetchFirstColumnDataProvider')]
    public function fetch_first_column_works(
        ?array $expectedResult,
        string $sql,
        array $parameters,
        array $parameterTypes,
        ?DTO\DecoderType $decoderType,
    ): void {
        // -- Act & Assert
        try {
            $result = $this->decodingConnection->fetchFirstColumn(
                sql: $sql,
                parameters: $parameters,
                parameterTypes: $parameterTypes,
                decoderType: $decoderType,
            );
            self::assertSame($expectedResult, $result);
        } catch (\Throwable $exception) {
            if ($exception instanceof ExpectationFailedException) {
                throw $exception;
            }

            $result = $exception::class;
            self::assertSame($expectedResult, $result);
        }
    }

    /**
     * @return array<string, array{
     *     expectedResult: array | null,
     *     sql: string,
     *     parameters: array,
     *     parameterTypes: array,
     *     decoderType: DTO\DecoderType | null,
     * }>
     */
    public static function fetchFirstColumnDataProvider(): array
    {
        return [
            'simple row without decoder types' => [
                'expectedResult' => [
                    '8c4b339b-75f4-499d-bf3a-56547b212aae',
                    '16092d20-c57d-44e0-ac87-3eff8b6bcd1e',
                ],
                'sql' => <<<'SQL'
                    SELECT
                        user_id
                    FROM (
                        VALUES
                            ('8c4b339b-75f4-499d-bf3a-56547b212aae'),
                            ('16092d20-c57d-44e0-ac87-3eff8b6bcd1e')
                    ) AS users(user_id)
                    SQL,
                'parameters' => [],
                'parameterTypes' => [],
                'decoderType' => null,
            ],
            'bool rows with decoder types' => [
                'expectedResult' => [
                    true,
                    null,
                    false,
                    false,
                ],
                'sql' => <<<'SQL'
                    SELECT
                        flag
                    FROM (
                        VALUES
                            ('true'),
                            (null),
                            ('false'),
                            (false)
                    ) AS flags(flag)
                    SQL,
                'parameters' => [],
                'parameterTypes' => [],
                'decoderType' => DTO\DecoderType::NULLABLE_BOOL,
            ],
            'json rows with decoder types' => [
                'expectedResult' => [
                    [
                        'name' => 'John Doe',
                        'userId' => '8c4b339b-75f4-499d-bf3a-56547b212aae',
                    ],
                    [
                        'name' => 'Jane Doe',
                        'userId' => '16092d20-c57d-44e0-ac87-3eff8b6bcd1e',
                    ],
                ],
                'sql' => <<<'SQL'
                    SELECT
                        user_identification
                    FROM (
                        VALUES
                            (jsonb_build_object(
                                'userId', '8c4b339b-75f4-499d-bf3a-56547b212aae',
                                'name', 'John Doe'
                            )),
                            (jsonb_build_object(
                                'userId', '16092d20-c57d-44e0-ac87-3eff8b6bcd1e',
                                'name', 'Jane Doe'
                            ))
                    ) AS flags(user_identification)
                    SQL,
                'parameters' => [],
                'parameterTypes' => [],
                'decoderType' => DTO\DecoderType::JSON,
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
            if ($exception instanceof ExpectationFailedException) {
                throw $exception;
            }

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
            'row with nested decoding' => [
                'expectedResult' => [
                    'name' => 'Stark Industries',
                    'projects' => [
                        [
                            'name' => 'Project 1',
                            'timeEntries' => [
                                [
                                    'hours' => 2.0,
                                ],
                                [
                                    'hours' => null,
                                ],
                            ],
                        ],
                        [
                            'name' => 'Project 2',
                            'timeEntries' => [
                                [
                                    'hours' => 1.5,
                                ],
                            ],
                        ],
                    ],
                ],
                'sql' => <<<'SQL'
                    SELECT
                        'Stark Industries' AS name,
                        jsonb_build_array(
                            jsonb_build_object(
                                'name', 'Project 1',
                                'timeEntries', jsonb_build_array(
                                    jsonb_build_object('hours', 2),
                                    jsonb_build_object('hours', null)
                                )
                            ),
                            jsonb_build_object(
                                'name', 'Project 2',
                                'timeEntries', jsonb_build_array(
                                    jsonb_build_object('hours', 1.5)
                                )
                            )
                        ) AS projects
                    SQL,
                'parameters' => [],
                'decoderTypes' => [
                    'projects' => DTO\DecoderType::JSON,
                    'projects.*.timeEntries.*.hours' => DTO\DecoderType::NULLABLE_FLOAT,
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
        ?string $indexedBy,
    ): void {
        // -- Act
        $result = $this->decodingConnection->fetchAllAssociative(
            sql: $sql,
            parameters: $parameters,
            decoderTypes: $decoderTypes,
            indexedBy: $indexedBy,
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
     *     indexedBy: string | null,
     * }>
     */
    public static function fetchAllAssociativeDataProvider(): array
    {
        return [
            'simple rows without decoder types and index by function' => [
                'expectedResult' => [
                    '8c4b339b-75f4-499d-bf3a-56547b212aae' => [
                        'userId' => '8c4b339b-75f4-499d-bf3a-56547b212aae',
                        'name' => 'John Doe',
                    ],
                    '16092d20-c57d-44e0-ac87-3eff8b6bcd1e' => [
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
                'indexedBy' => 'userId',
            ],
            'simple rows without decoder types' => [
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
                'indexedBy' => null,
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
                'indexedBy' => null,
            ],
            'rows with nested decoding' => [
                'expectedResult' => [
                    [
                        'name' => 'Stark Industries',
                        'projects' => [
                            [
                                'name' => 'Project 1',
                                'timeEntries' => [
                                    [
                                        'hours' => 2.0,
                                    ],
                                    [
                                        'hours' => null,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Wayne Enterprises',
                        'projects' => [],
                    ],
                ],
                'sql' => <<<'SQL'
                    SELECT
                        name,
                        projects
                    FROM (
                        VALUES
                            ('Stark Industries', '[{"name": "Project 1", "timeEntries": [{"hours": 2}, {"hours": null}]}]'::jsonb),
                            ('Wayne Enterprises', null)
                    ) AS companies(name, projects)
                    SQL,
                'parameters' => [],
                'decoderTypes' => [
                    'projects.*.timeEntries.*.hours' => DTO\DecoderType::NULLABLE_FLOAT,
                    'projects' => DTO\DecoderType::JSON_WITH_EMPTY_ARRAY_ON_NULL,
                ],
                'indexedBy' => null,
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
                'indexedBy' => null,
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
            if ($exception instanceof ExpectationFailedException) {
                throw $exception;
            }

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
            if ($exception instanceof ExpectationFailedException) {
                throw $exception;
            }

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

    #[Test]
    public function decode_item_works_on_second_level(): void
    {
        // -- Arrange
        $item = [
            'name' => 'John Doe',
            'project' => [
                'name' => 'Project 1',
                'budget' => '1500',
                'isArchived' => 'false',
            ],
        ];
        $decoderTypes = [
            'project.budget' => DTO\DecoderType::INT,
            'project.isArchived' => DTO\DecoderType::BOOL,
        ];

        // -- Act
        DecodingConnection::decodeItem(
            item: $item,
            decoderTypes: $decoderTypes,
        );

        // -- Assert
        self::assertSame(
            [
                'name' => 'John Doe',
                'project' => [
                    'name' => 'Project 1',
                    'budget' => 1500,
                    'isArchived' => false,
                ],
            ],
            $item,
        );
    }

    #[Test]
    public function decode_item_works_on_third_level(): void
    {
        // -- Arrange
        $item = [
            'name' => 'John Doe',
            'project' => [
                'name' => 'Project 1',
                'responsible' => [
                    'name' => 'Jane Doe',
                    'hourlyRate' => 85,
                    'settings' => '{"isNotifiedByEmail": true}',
                ],
            ],
        ];
        $decoderTypes = [
            'project.responsible.hourlyRate' => DTO\DecoderType::FLOAT,
            'project.responsible.settings' => DTO\DecoderType::JSON,
        ];

        // -- Act
        DecodingConnection::decodeItem(
            item: $item,
            decoderTypes: $decoderTypes,
        );

        // -- Assert
        self::assertSame(
            [
                'name' => 'John Doe',
                'project' => [
                    'name' => 'Project 1',
                    'responsible' => [
                        'name' => 'Jane Doe',
                        'hourlyRate' => 85.0,
                        'settings' => [
                            'isNotifiedByEmail' => true,
                        ],
                    ],
                ],
            ],
            $item,
        );
    }

    #[Test]
    public function decode_item_works_with_array(): void
    {
        // -- Arrange
        $item = [
            'name' => 'John Doe',
            'users' => [
                [
                    'name' => 'Jane Doe',
                    'hours' => 5,
                ],
                [
                    'name' => 'Richard Roe',
                    'hours' => null,
                ],
                [
                    'name' => 'Mary Roe',
                    'hours' => '7.5',
                ],
            ],
        ];
        $decoderTypes = [
            'users.*.hours' => DTO\DecoderType::NULLABLE_FLOAT,
        ];

        // -- Act
        DecodingConnection::decodeItem(
            item: $item,
            decoderTypes: $decoderTypes,
        );

        // -- Assert
        self::assertSame(
            [
                'name' => 'John Doe',
                'users' => [
                    [
                        'name' => 'Jane Doe',
                        'hours' => 5.0,
                    ],
                    [
                        'name' => 'Richard Roe',
                        'hours' => null,
                    ],
                    [
                        'name' => 'Mary Roe',
                        'hours' => 7.5,
                    ],
                ],
            ],
            $item,
        );
    }

    #[Test]
    public function decode_item_works_with_nested_arrays(): void
    {
        // -- Arrange
        $item = [
            'projects' => [
                [
                    'name' => 'Project 1',
                    'timeEntries' => [
                        [
                            'hours' => 2,
                        ],
                        [
                            'hours' => null,
                        ],
                    ],
                ],
                [
                    'name' => 'Project 2',
                    'timeEntries' => [],
                ],
                [
                    'name' => 'Project 3',
                    'timeEntries' => [
                        [
                            'hours' => 1.5,
                        ],
                    ],
                ],
            ],
        ];
        $decoderTypes = [
            'projects.*.timeEntries.*.hours' => DTO\DecoderType::NULLABLE_FLOAT,
        ];

        // -- Act
        DecodingConnection::decodeItem(
            item: $item,
            decoderTypes: $decoderTypes,
        );

        // -- Assert
        self::assertSame(
            [
                'projects' => [
                    [
                        'name' => 'Project 1',
                        'timeEntries' => [
                            [
                                'hours' => 2.0,
                            ],
                            [
                                'hours' => null,
                            ],
                        ],
                    ],
                    [
                        'name' => 'Project 2',
                        'timeEntries' => [],
                    ],
                    [
                        'name' => 'Project 3',
                        'timeEntries' => [
                            [
                                'hours' => 1.5,
                            ],
                        ],
                    ],
                ],
            ],
            $item,
        );
    }

    #[Test]
    public function decode_item_decodes_parent_value_before_nested_values(): void
    {
        // -- Arrange
        $item = [
            'projects' => '[{"name": "Project 1", "timeEntries": [{"hours": 2}, {"hours": null}]}]',
        ];
        // The nested keys are defined before the parent key on purpose.
        $decoderTypes = [
            'projects.*.timeEntries.*.hours' => DTO\DecoderType::NULLABLE_FLOAT,
            'projects' => DTO\DecoderType::JSON,
        ];

        // -- Act
        DecodingConnection::decodeItem(
            item: $item,
            decoderTypes: $decoderTypes,
        );

        // -- Assert
        self::assertSame(
            [
                'projects' => [
                    [
                        'name' => 'Project 1',
                        'timeEntries' => [
                            [
                                'hours' => 2.0,
                            ],
                            [
                                'hours' => null,
                            ],
                        ],
                    ],
                ],
            ],
            $item,
        );
    }

    #[Test]
    public function decode_item_works_with_json_strings_nested_in_json(): void
    {
        // -- Arrange
        $item = [
            'projects' => '[{"name": "Project 1", "timeEntries": "[{\\"hours\\": 2}]"}, {"name": "Project 2", "timeEntries": null}]',
        ];
        $decoderTypes = [
            'projects' => DTO\DecoderType::JSON,
            'projects.*.timeEntries' => DTO\DecoderType::JSON_WITH_EMPTY_ARRAY_ON_NULL,
            'projects.*.timeEntries.*.hours' => DTO\DecoderType::NULLABLE_FLOAT,
        ];

        // -- Act
        DecodingConnection::decodeItem(
            item: $item,
            decoderTypes: $decoderTypes,
        );

        // -- Assert
        self::assertSame(
            [
                'projects' => [
                    [
                        'name' => 'Project 1',
                        'timeEntries' => [
                            [
                                'hours' => 2.0,
                            ],
                        ],
                    ],
                    [
                        'name' => 'Project 2',
                        'timeEntries' => [],
                    ],
                ],
            ],
            $item,
        );
    }

    #[Test]
    public function decode_item_ignores_null_values_on_the_way_to_the_nested_value(): void
    {
        // -- Arrange
        $item = [
            'project' => null,
            'users' => null,
            'projects' => [
                null,
                [
                    'name' => 'Project 1',
                    'timeEntries' => null,
                    'responsible' => null,
                ],
                [
                    'name' => 'Project 2',
                    'timeEntries' => [
                        null,
                        [
                            'hours' => 2,
                        ],
                    ],
                    'responsible' => [
                        'hourlyRate' => '85',
                    ],
                ],
            ],
        ];
        $decoderTypes = [
            'project.budget' => DTO\DecoderType::INT,
            'users.*.hours' => DTO\DecoderType::FLOAT,
            'projects.*.timeEntries.*.hours' => DTO\DecoderType::FLOAT,
            'projects.*.responsible.hourlyRate' => DTO\DecoderType::INT,
        ];

        // -- Act
        DecodingConnection::decodeItem(
            item: $item,
            decoderTypes: $decoderTypes,
        );

        // -- Assert
        self::assertSame(
            [
                'project' => null,
                'users' => null,
                'projects' => [
                    null,
                    [
                        'name' => 'Project 1',
                        'timeEntries' => null,
                        'responsible' => null,
                    ],
                    [
                        'name' => 'Project 2',
                        'timeEntries' => [
                            null,
                            [
                                'hours' => 2.0,
                            ],
                        ],
                        'responsible' => [
                            'hourlyRate' => 85,
                        ],
                    ],
                ],
            ],
            $item,
        );
    }

    #[Test]
    public function decode_item_ignores_missing_keys(): void
    {
        // -- Arrange
        $item = [
            'name' => 'John Doe',
            'project' => [
                'name' => 'Project 1',
            ],
            'users' => [
                [
                    'name' => 'Jane Doe',
                ],
                [
                    'name' => 'Richard Roe',
                    'hours' => '5',
                ],
            ],
        ];
        $decoderTypes = [
            'notExisting' => DTO\DecoderType::INT,
            'notExisting.budget' => DTO\DecoderType::INT,
            'project.budget' => DTO\DecoderType::INT,
            'project.responsible.hourlyRate' => DTO\DecoderType::INT,
            'users.*.hours' => DTO\DecoderType::FLOAT,
        ];

        // -- Act
        DecodingConnection::decodeItem(
            item: $item,
            decoderTypes: $decoderTypes,
        );

        // -- Assert
        self::assertSame(
            [
                'name' => 'John Doe',
                'project' => [
                    'name' => 'Project 1',
                ],
                'users' => [
                    [
                        'name' => 'Jane Doe',
                    ],
                    [
                        'name' => 'Richard Roe',
                        'hours' => 5.0,
                    ],
                ],
            ],
            $item,
        );
    }

    #[Test]
    public function decode_item_fails_when_level_is_not_an_array(): void
    {
        // -- Arrange
        $item = [
            'project' => '{"name": "Project 1", "budget": "1500"}',
        ];
        $decoderTypes = [
            'project.budget' => DTO\DecoderType::INT,
        ];

        // -- Assert
        $this->expectException(Exception\DecoderTypeKeyLevelIsNotAnArray::class);
        $this->expectExceptionMessage('The value of level "project" of the decoder type key "project.budget" must be an array or null.');

        // -- Act
        DecodingConnection::decodeItem(
            item: $item,
            decoderTypes: $decoderTypes,
        );
    }

    #[Test]
    public function decode_item_fails_when_element_of_array_is_not_an_array(): void
    {
        // -- Arrange
        $item = [
            'users' => [
                '{"name": "Jane Doe", "hours": "5"}',
            ],
        ];
        $decoderTypes = [
            'users.*.hours' => DTO\DecoderType::FLOAT,
        ];

        // -- Assert
        $this->expectException(Exception\DecoderTypeKeyLevelIsNotAnArray::class);
        $this->expectExceptionMessage('The value of level "*" of the decoder type key "users.*.hours" must be an array or null.');

        // -- Act
        DecodingConnection::decodeItem(
            item: $item,
            decoderTypes: $decoderTypes,
        );
    }

    #[Test]
    public function decode_item_fails_when_key_starts_with_array_identifier(): void
    {
        // -- Arrange
        $item = [
            'hours' => '5',
        ];
        $decoderTypes = [
            '*.hours' => DTO\DecoderType::FLOAT,
        ];

        // -- Assert
        $this->expectException(DTO\Exception\ResultTransformationKeyCanNotStartWithAnArrayIdentifier::class);

        // -- Act
        DecodingConnection::decodeItem(
            item: $item,
            decoderTypes: $decoderTypes,
        );
    }

    #[Test]
    public function decode_item_fails_when_key_ends_with_array_identifier(): void
    {
        // -- Arrange
        $item = [
            'hours' => ['5'],
        ];
        $decoderTypes = [
            'hours.*' => DTO\DecoderType::FLOAT,
        ];

        // -- Assert
        $this->expectException(DTO\Exception\ResultTransformationKeyCanNotEndWithAnArrayIdentifier::class);

        // -- Act
        DecodingConnection::decodeItem(
            item: $item,
            decoderTypes: $decoderTypes,
        );
    }

    #[Test]
    public function decode_results_works_with_nested_keys(): void
    {
        // -- Arrange
        $results = [
            [
                'name' => 'Stark Industries',
                'projects' => '[{"name": "Project 1", "timeEntries": [{"hours": 2}, {"hours": null}]}]',
            ],
            [
                'name' => 'Wayne Enterprises',
                'projects' => '[{"name": "Project 2", "timeEntries": [{"hours": "1.5"}]}]',
            ],
            [
                'name' => 'Acme Corporation',
                'projects' => null,
            ],
        ];
        $decoderTypes = [
            'projects.*.timeEntries.*.hours' => DTO\DecoderType::NULLABLE_FLOAT,
            'projects' => DTO\DecoderType::NULLABLE_JSON,
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
                    'name' => 'Stark Industries',
                    'projects' => [
                        [
                            'name' => 'Project 1',
                            'timeEntries' => [
                                [
                                    'hours' => 2.0,
                                ],
                                [
                                    'hours' => null,
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'Wayne Enterprises',
                    'projects' => [
                        [
                            'name' => 'Project 2',
                            'timeEntries' => [
                                [
                                    'hours' => 1.5,
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'Acme Corporation',
                    'projects' => null,
                ],
            ],
            $results,
        );
    }
}
