<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Serializer;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;

final readonly class DecodingConnection
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * @param list<mixed>|array<string, mixed>                                     $parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $parameterTypes
     * @param array<string, DTO\DecoderType>                                       $decoderTypes
     *
     * @return array<string, mixed>|null
     */
    public function fetchAssociative(
        string $sql,
        array $parameters = [],
        array $parameterTypes = [],
        array $decoderTypes = [],
    ): ?array {
        /** @var array<string, mixed>|false $result */
        $result = $this->connection->fetchAssociative($sql, $parameters, $parameterTypes);

        if ($result === false) {
            return null;
        }

        self::decodeItem($result, $decoderTypes);

        return $result;
    }

    /**
     * @param list<mixed>|array<string, mixed>                                     $parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $parameterTypes
     * @param array<string, DTO\DecoderType>                                       $decoderTypes
     */
    public function fetchAllAssociative(
        string $sql,
        array $parameters = [],
        array $parameterTypes = [],
        array $decoderTypes = [],
    ): array {
        /** @var array<int, array<string, mixed>> $result */
        $result = $this->connection->fetchAllAssociative($sql, $parameters, $parameterTypes);

        self::decodeResults($result, $decoderTypes);

        return $result;
    }

    /**
     * @param list<mixed>|array<string, mixed>                                     $parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $parameterTypes
     */
    public function fetchInt(
        string $sql,
        array $parameters = [],
        array $parameterTypes = [],
    ): int {
        return (int) $this->connection->fetchOne($sql, $parameters, $parameterTypes);
    }

    /**
     * @param list<mixed>|array<string, mixed>                                     $parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $parameterTypes
     *
     * @throws Exception\QueryDidNotReturnExactlyOneResult
     * @throws Exception\QueryDidNotReturnABoolean
     */
    public function fetchBool(
        string $sql,
        array $parameters = [],
        array $parameterTypes = [],
    ): bool {
        /** @var array<int, mixed> $result */
        $result = $this->connection->fetchFirstColumn($sql, $parameters, $parameterTypes);

        if (count($result) !== 1) {
            throw new Exception\QueryDidNotReturnExactlyOneResult();
        }

        $value = $result[0];
        if (!is_bool($value)) {
            throw new Exception\QueryDidNotReturnABoolean();
        }

        return $value;
    }

    /**
     * @param array<int, array<string, mixed>> $data
     * @param array<string, DTO\DecoderType>   $decoderTypes
     *
     * @internal
     */
    public static function decodeResults(
        array &$data,
        array $decoderTypes,
    ): void {
        foreach ($data as &$item) {
            self::decodeItem($item, $decoderTypes);
        }
    }

    /**
     * @param array<string, mixed>           $item
     * @param array<string, DTO\DecoderType> $decoderTypes
     *
     * @psalm-suppress MixedAssignment Mixed is used here by design
     * @psalm-suppress MixedArgument Mixed is used here by design
     *
     * @internal
     */
    public static function decodeItem(
        array &$item,
        array $decoderTypes,
    ): void {
        $relevantKeys = array_keys($decoderTypes);

        foreach ($item as $itemKey => &$itemValue) {
            if (!in_array($itemKey, $relevantKeys, true)) {
                $item[$itemKey] = $itemValue;
                continue;
            }

            $decoderType = $decoderTypes[$itemKey];
            $item[$itemKey] = match ($decoderType) {
                DTO\DecoderType::INT => (int) $itemValue,
                DTO\DecoderType::NULLABLE_INT => $itemValue === null
                    ? null
                    : (int) $itemValue,
                DTO\DecoderType::FLOAT => (float) $itemValue,
                DTO\DecoderType::NULLABLE_FLOAT => $itemValue === null
                    ? null
                    : (float) $itemValue,
                DTO\DecoderType::JSON => json_decode(
                    $itemValue,
                    true,
                    512,
                    \JSON_THROW_ON_ERROR,
                ),
                DTO\DecoderType::NULLABLE_JSON => $itemValue === null
                    ? null
                    : json_decode(
                        $itemValue,
                        true,
                        512,
                        \JSON_THROW_ON_ERROR,
                    ),
                DTO\DecoderType::JSON_WITH_EMPTY_ARRAY_ON_NULL => $itemValue === null
                    ? []
                    : json_decode(
                        $itemValue,
                        true,
                        512,
                        \JSON_THROW_ON_ERROR,
                    ),
            };
        }
    }
}
