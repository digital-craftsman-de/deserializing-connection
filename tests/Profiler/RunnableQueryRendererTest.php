<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Profiler;

use DigitalCraftsman\DeserializingConnection\Test\ConnectionTestCase;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(RunnableQueryRenderer::class)]
#[CoversClass(RunnableQueryVisitor::class)]
#[CoversClass(Exception\QueryCouldNotBeRendered::class)]
final class RunnableQueryRendererTest extends ConnectionTestCase
{
    private RunnableQueryRenderer $runnableQueryRenderer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->runnableQueryRenderer = new RunnableQueryRenderer($this->connection);
    }

    /**
     * The rendered query must return the same result as the query with parameters.
     */
    #[Test]
    #[DataProvider('renderDataProvider')]
    public function render_works(
        string $expectedSql,
        string $sql,
        array $parameters,
        array $parameterTypes,
    ): void {
        // -- Act
        $runnableSql = $this->runnableQueryRenderer->render($sql, $parameters, $parameterTypes);

        // -- Assert
        self::assertSame($expectedSql, $runnableSql);
        self::assertSame(
            $this->connection->fetchAllAssociative($sql, $parameters, $parameterTypes),
            $this->connection->fetchAllAssociative($runnableSql),
        );
    }

    /**
     * @return array<string, array{
     *   0: string,
     *   1: string,
     *   2: array,
     *   3: array,
     * }>
     */
    public static function renderDataProvider(): array
    {
        return [
            'without parameters' => [
                <<<'SQL'
                    SELECT 'value ? :value' AS value
                    SQL,
                <<<'SQL'
                    SELECT 'value ? :value' AS value
                    SQL,
                [],
                [],
            ],
            'positional parameters without types' => [
                <<<'SQL'
                    SELECT '1' AS number, 'O''Reilly' AS name, '1.5' AS float, '1' AS bool, NULL AS empty
                    SQL,
                <<<'SQL'
                    SELECT ? AS number, ? AS name, ? AS float, ? AS bool, ? AS empty
                    SQL,
                [1, "O'Reilly", 1.5, true, null],
                [],
            ],
            'positional parameters with parameter types' => [
                <<<'SQL'
                    SELECT '5' AS number, '6' AS number_as_string, 't' AS is_true, 'f' AS is_false, NULL AS empty
                    SQL,
                <<<'SQL'
                    SELECT ? AS number, ? AS number_as_string, ? AS is_true, ? AS is_false, ? AS empty
                    SQL,
                [5, '6', true, 0, null],
                [ParameterType::INTEGER, ParameterType::INTEGER, ParameterType::BOOLEAN, ParameterType::BOOLEAN, ParameterType::INTEGER],
            ],
            'named parameters with casts, string literals and comments' => [
                <<<'SQL'
                    SELECT '417df760-0d16-408f-8201-ec7760dee9fb'::uuid AS id, ':notAParameter ?' AS text -- :alsoNotAParameter ?
                    WHERE '5' = '5'
                    SQL,
                <<<'SQL'
                    SELECT :userId::uuid AS id, ':notAParameter ?' AS text -- :alsoNotAParameter ?
                    WHERE :number = :number
                    SQL,
                [
                    'userId' => '417df760-0d16-408f-8201-ec7760dee9fb',
                    'number' => 5,
                ],
                [
                    'number' => ParameterType::INTEGER,
                ],
            ],
            'array parameters' => [
                <<<'SQL'
                    SELECT 2 IN ('1', '2', '3') AS in_numbers, 'b''c' IN ('a', 'b''c') AS in_strings, 1 IN (NULL) AS in_empty
                    SQL,
                <<<'SQL'
                    SELECT 2 IN (:numbers) AS in_numbers, 'b''c' IN (:strings) AS in_strings, 1 IN (:empty) AS in_empty
                    SQL,
                [
                    'numbers' => [1, 2, 3],
                    'strings' => ['a', "b'c"],
                    'empty' => [],
                ],
                [
                    'numbers' => ArrayParameterType::INTEGER,
                    'strings' => ArrayParameterType::STRING,
                    'empty' => ArrayParameterType::INTEGER,
                ],
            ],
            'doctrine types' => [
                <<<'SQL'
                    SELECT '{"name":"John"}'::jsonb AS data, 't' AS is_active
                    SQL,
                <<<'SQL'
                    SELECT :data::jsonb AS data, :isActive AS is_active
                    SQL,
                [
                    'data' => ['name' => 'John'],
                    'isActive' => true,
                ],
                [
                    'data' => Types::JSON,
                    'isActive' => Type::getType(Types::BOOLEAN),
                ],
            ],
            'stringable value' => [
                <<<'SQL'
                    SELECT '417df760-0d16-408f-8201-ec7760dee9fb' AS id
                    SQL,
                <<<'SQL'
                    SELECT :userId AS id
                    SQL,
                [
                    'userId' => new class implements \Stringable {
                        #[\Override]
                        public function __toString(): string
                        {
                            return '417df760-0d16-408f-8201-ec7760dee9fb';
                        }
                    },
                ],
                [],
            ],
        ];
    }

    /**
     * PDO uses "??" as escape sequence for a literal question mark, even for queries without parameters. The rendered query must contain the
     * unescaped operator, as it's meant to be executed outside of PDO. To compare the results through PDO, it's escaped again.
     */
    #[Test]
    #[DataProvider('renderWithEscapedQuestionMarksDataProvider')]
    public function render_works_with_escaped_question_marks(
        string $expectedSql,
        string $sql,
        array $parameters,
    ): void {
        // -- Act
        $runnableSql = $this->runnableQueryRenderer->render($sql, $parameters, []);

        // -- Assert
        self::assertSame($expectedSql, $runnableSql);
        self::assertSame(
            $this->connection->fetchAllAssociative($sql, $parameters),
            $this->connection->fetchAllAssociative(str_replace('?', '??', $runnableSql)),
        );
    }

    /**
     * @return array<string, array{
     *   0: string,
     *   1: string,
     *   2: array,
     * }>
     */
    public static function renderWithEscapedQuestionMarksDataProvider(): array
    {
        return [
            'with parameters' => [
                <<<'SQL'
                    SELECT '{"a": 1}'::jsonb ? 'a' AS has_key, '{"a": 1}'::jsonb ?| array['b'] AS has_any_key
                    SQL,
                <<<'SQL'
                    SELECT '{"a": 1}'::jsonb ?? :key AS has_key, '{"a": 1}'::jsonb ??| array['b'] AS has_any_key
                    SQL,
                [
                    'key' => 'a',
                ],
            ],
            'without parameters' => [
                <<<'SQL'
                    SELECT '{"a": 1}'::jsonb ? 'a' AS has_key
                    SQL,
                <<<'SQL'
                    SELECT '{"a": 1}'::jsonb ?? 'a' AS has_key
                    SQL,
                [],
            ],
        ];
    }

    #[Test]
    public function render_fails_with_missing_positional_parameter(): void
    {
        // -- Assert
        $this->expectException(Exception\QueryCouldNotBeRendered::class);
        $this->expectExceptionMessage('The runnable query could not be rendered: Positional parameter at index 1 is missing');

        // -- Act
        $this->runnableQueryRenderer->render('SELECT ?, ?', [1], []);
    }

    #[Test]
    public function render_fails_with_missing_named_parameter(): void
    {
        // -- Assert
        $this->expectException(Exception\QueryCouldNotBeRendered::class);
        $this->expectExceptionMessage('The runnable query could not be rendered: Named parameter "missing" is missing');

        // -- Act
        $this->runnableQueryRenderer->render('SELECT :existing, :missing', ['existing' => 1], []);
    }

    #[Test]
    public function render_fails_with_array_parameter_type_without_array(): void
    {
        // -- Assert
        $this->expectException(Exception\QueryCouldNotBeRendered::class);
        $this->expectExceptionMessage('The runnable query could not be rendered: Parameter "ids" with an array parameter type must be an array');

        // -- Act
        $this->runnableQueryRenderer->render('SELECT 1 WHERE 1 IN (:ids)', ['ids' => 1], ['ids' => ArrayParameterType::INTEGER]);
    }

    #[Test]
    public function render_fails_with_value_that_is_not_stringable(): void
    {
        // -- Assert
        $this->expectException(Exception\QueryCouldNotBeRendered::class);
        $this->expectExceptionMessage('The runnable query could not be rendered: Value of type stdClass can not be rendered');

        // -- Act
        $this->runnableQueryRenderer->render('SELECT :value', ['value' => new \stdClass()], []);
    }

    #[Test]
    public function render_fails_with_resource(): void
    {
        // -- Arrange
        $resource = fopen('php://memory', 'rb');

        // -- Assert
        $this->expectException(Exception\QueryCouldNotBeRendered::class);
        $this->expectExceptionMessage('The runnable query could not be rendered: Value of type resource (stream) can not be rendered');

        // -- Act
        $this->runnableQueryRenderer->render('SELECT :file', ['file' => $resource], ['file' => ParameterType::LARGE_OBJECT]);
    }
}
