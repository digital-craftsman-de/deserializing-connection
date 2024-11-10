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
