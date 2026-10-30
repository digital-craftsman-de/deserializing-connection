<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Profiler;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Type;

/**
 * Renders the query with all parameters inlined as literals, so that it can be copied and executed directly against the database.
 *
 * The query is parsed with the same parser that is used by Doctrine DBAL to expand the parameters. Therefore, string literals, comments,
 * casts (::) and escaped question marks (??) are handled the same way as when the query is executed. PDO also handles escaped question
 * marks in queries without parameters, therefore every query is parsed.
 */
final readonly class RunnableQueryRenderer
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * @param list<mixed>|array<string, mixed>                                        $parameters
     * @param array<int<0, max>|string, ArrayParameterType|ParameterType|Type|string> $parameterTypes
     *
     * @throws Exception\QueryCouldNotBeRendered
     */
    public function render(
        string $sql,
        array $parameters,
        array $parameterTypes,
    ): string {
        $platform = $this->connection->getDatabasePlatform();

        $visitor = new RunnableQueryVisitor(
            platform: $platform,
            parameters: $parameters,
            parameterTypes: $parameterTypes,
        );

        $platform->createSQLParser()->parse($sql, $visitor);

        return $visitor->sql();
    }
}
