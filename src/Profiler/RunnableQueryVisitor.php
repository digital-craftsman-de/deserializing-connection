<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Profiler;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\SQL\Parser\Visitor;
use Doctrine\DBAL\Types\Type;

/**
 * Mirrors the parameter handling of Doctrine DBAL (expansion of array parameters and conversion through types), but instead of
 * placeholders, the converted values are inlined as literals.
 *
 * All values are rendered as string literals. That's the same as how PDO sends the parameters to PostgreSQL: As text without a specific
 * type, so that PostgreSQL infers the type from the context.
 *
 * @internal
 */
final class RunnableQueryVisitor implements Visitor
{
    private int $positionalParameterIndex = 0;

    private bool $isWithinEscapedQuestionMark = false;

    /** @var list<string> */
    private array $sqlParts = [];

    /**
     * @param list<mixed>|array<string, mixed>                                        $parameters
     * @param array<int<0, max>|string, ArrayParameterType|ParameterType|Type|string> $parameterTypes
     */
    public function __construct(
        private readonly AbstractPlatform $platform,
        private readonly array $parameters,
        private readonly array $parameterTypes,
    ) {
    }

    #[\Override]
    public function acceptPositionalParameter(string $sql): void
    {
        $index = $this->positionalParameterIndex;

        if (!array_key_exists($index, $this->parameters)) {
            throw new Exception\QueryCouldNotBeRendered(sprintf('Positional parameter at index %d is missing', $index));
        }

        $this->sqlParts[] = $this->renderParameter($index);

        ++$this->positionalParameterIndex;
    }

    #[\Override]
    public function acceptNamedParameter(string $sql): void
    {
        $name = substr($sql, 1);

        if (!array_key_exists($name, $this->parameters)) {
            throw new Exception\QueryCouldNotBeRendered(sprintf('Named parameter "%s" is missing', $name));
        }

        $this->sqlParts[] = $this->renderParameter($name);
    }

    /**
     * PDO uses "??" as escape sequence for a literal question mark (e.g. for the JSONB operators of PostgreSQL). The parser returns both
     * question marks as separate parts, so only the first one is kept. Which one is skipped doesn't make a difference.
     */
    #[\Override]
    public function acceptOther(string $sql): void
    {
        if ($sql === '?') {
            $this->isWithinEscapedQuestionMark = !$this->isWithinEscapedQuestionMark;

            if (!$this->isWithinEscapedQuestionMark) {
                return;
            }
        }

        $this->sqlParts[] = $sql;
    }

    public function sql(): string
    {
        return implode('', $this->sqlParts);
    }

    private function renderParameter(int | string $key): string
    {
        $value = $this->parameters[$key];
        $type = $this->parameterTypes[$key] ?? ParameterType::STRING;

        if (!$type instanceof ArrayParameterType) {
            return $this->renderValue($value, $type);
        }

        if (!is_array($value)) {
            throw new Exception\QueryCouldNotBeRendered(sprintf('Parameter "%s" with an array parameter type must be an array', $key));
        }

        // Same as with Doctrine DBAL
        if (count($value) === 0) {
            return 'NULL';
        }

        $elementType = ArrayParameterType::toElementParameterType($type);

        return implode(', ', array_map(
            fn (mixed $item): string => $this->renderValue($item, $elementType),
            $value,
        ));
    }

    private function renderValue(
        mixed $value,
        ParameterType | Type | string $type,
    ): string {
        if (is_string($type)) {
            $type = Type::getType($type);
        }

        if ($type instanceof Type) {
            /** @psalm-suppress MixedAssignment */
            $value = $type->convertToDatabaseValue($value, $this->platform);
            $type = $type->getBindingType();
        }

        if ($value === null) {
            return 'NULL';
        }

        // PDO sends booleans as "t" or "f"
        if ($type === ParameterType::BOOLEAN) {
            return $value
                ? "'t'"
                : "'f'";
        }

        return $this->renderString($value);
    }

    private function renderString(mixed $value): string
    {
        if (!is_scalar($value)
            && !$value instanceof \Stringable
        ) {
            throw new Exception\QueryCouldNotBeRendered(sprintf('Value of type %s can not be rendered', get_debug_type($value)));
        }

        return $this->platform->quoteStringLiteral((string) $value);
    }
}
