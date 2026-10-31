<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Serializer;

use DigitalCraftsman\DeserializingConnection\Profiler\NullTracer;
use DigitalCraftsman\DeserializingConnection\Profiler\Trace;
use DigitalCraftsman\DeserializingConnection\Profiler\Tracer;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Type;

/**
 * The methods which are used by the DeserializingConnection accept an existing trace. When it's supplied, the steps are recorded in this
 * trace instead of starting a new one.
 */
final readonly class DecodingConnection
{
    public function __construct(
        private Connection $connection,
        private Tracer $tracer = new NullTracer(),
    ) {
    }

    /**
     * @param list<mixed>|array<string, mixed>                                        $parameters
     * @param array<int<0, max>|string, ArrayParameterType|ParameterType|Type|string> $parameterTypes
     *
     * @return array<string, mixed>|null
     */
    #[\NoDiscard('as the result of the query would be lost')]
    public function fetchOne(
        string $sql,
        array $parameters = [],
        array $parameterTypes = [],
        ?DTO\DecoderType $decoderType = null,
        ?Trace $trace = null,
    ): mixed {
        $trace ??= $this->tracer->startTrace(__METHOD__, $sql, $parameters, $parameterTypes, [
            'decoderType' => $decoderType,
        ]);

        try {
            /** @var array<int, mixed> $result */
            $result = $this->connection->fetchFirstColumn($sql, $parameters, $parameterTypes);

            $trace->recordRawResult($result);

            if (count($result) === 0) {
                return null;
            }

            $firstResult = $result[0];

            if ($decoderType !== null) {
                $firstResult = self::decodeValue($firstResult, $decoderType);
            }

            $trace->recordDecodedResult($firstResult);

            return $firstResult;
        } catch (\Throwable $exception) {
            $trace->recordException($exception);

            throw $exception;
        }
    }

    /**
     * @param list<mixed>|array<string, mixed>                                        $parameters
     * @param array<int<0, max>|string, ArrayParameterType|ParameterType|Type|string> $parameterTypes
     * @param array<string, DTO\DecoderType>                                          $decoderTypes
     *
     * @return array<string, mixed>|null
     */
    #[\NoDiscard('as the result of the query would be lost')]
    public function fetchAssociative(
        string $sql,
        array $parameters = [],
        array $parameterTypes = [],
        array $decoderTypes = [],
        ?Trace $trace = null,
    ): ?array {
        $trace ??= $this->tracer->startTrace(__METHOD__, $sql, $parameters, $parameterTypes, [
            'decoderTypes' => $decoderTypes,
        ]);

        try {
            /** @var array<string, mixed>|false $result */
            $result = $this->connection->fetchAssociative($sql, $parameters, $parameterTypes);

            $trace->recordRawResult($result);

            if ($result === false) {
                return null;
            }

            self::decodeItem($result, $decoderTypes);

            $trace->recordDecodedResult($result);

            return $result;
        } catch (\Throwable $exception) {
            $trace->recordException($exception);

            throw $exception;
        }
    }

    /**
     * @param list<mixed>|array<string, mixed>                                        $parameters
     * @param array<int<0, max>|string, ArrayParameterType|ParameterType|Type|string> $parameterTypes
     * @param array<string, DTO\DecoderType>                                          $decoderTypes
     */
    #[\NoDiscard('as the result of the query would be lost')]
    public function fetchAllAssociative(
        string $sql,
        array $parameters = [],
        array $parameterTypes = [],
        array $decoderTypes = [],
        ?string $indexedBy = null,
        ?Trace $trace = null,
    ): array {
        $trace ??= $this->tracer->startTrace(__METHOD__, $sql, $parameters, $parameterTypes, [
            'decoderTypes' => $decoderTypes,
            'indexedBy' => $indexedBy,
        ]);

        try {
            /** @var array<int, array<string, mixed>> $result */
            $result = $this->connection->fetchAllAssociative($sql, $parameters, $parameterTypes);

            $trace->recordRawResult($result);

            self::decodeResults($result, $decoderTypes);

            $decodedResult = $indexedBy !== null
                ? array_column($result, null, $indexedBy)
                : $result;

            $trace->recordDecodedResult($decodedResult);

            return $decodedResult;
        } catch (\Throwable $exception) {
            $trace->recordException($exception);

            throw $exception;
        }
    }

    /**
     * @param list<mixed>|array<string, mixed>                                        $parameters
     * @param array<int<0, max>|string, ArrayParameterType|ParameterType|Type|string> $parameterTypes
     */
    #[\NoDiscard('as the result of the query would be lost')]
    public function fetchFirstColumn(
        string $sql,
        array $parameters = [],
        array $parameterTypes = [],
        ?DTO\DecoderType $decoderType = null,
    ): array {
        $trace = $this->tracer->startTrace(__METHOD__, $sql, $parameters, $parameterTypes, [
            'decoderType' => $decoderType,
        ]);

        try {
            /** @var array<int, array<string, mixed>> $result */
            $result = $this->connection->fetchFirstColumn($sql, $parameters, $parameterTypes);

            $trace->recordRawResult($result);

            if ($decoderType !== null) {
                foreach ($result as $key => $value) {
                    $result[$key] = self::decodeValue($value, $decoderType);
                }
            }

            $trace->recordDecodedResult($result);

            return $result;
        } catch (\Throwable $exception) {
            $trace->recordException($exception);

            throw $exception;
        }
    }

    /**
     * @param list<mixed>|array<string, mixed>                                        $parameters
     * @param array<int<0, max>|string, ArrayParameterType|ParameterType|Type|string> $parameterTypes
     *
     * @throws Exception\QueryDidNotReturnExactlyOneResult
     * @throws Exception\QueryDidNotReturnAnInt
     */
    #[\NoDiscard('as the result of the query would be lost')]
    public function fetchInt(
        string $sql,
        array $parameters = [],
        array $parameterTypes = [],
    ): int {
        $trace = $this->tracer->startTrace(__METHOD__, $sql, $parameters, $parameterTypes, []);

        try {
            /** @var array<int, mixed> $result */
            $result = $this->connection->fetchFirstColumn($sql, $parameters, $parameterTypes);

            $trace->recordRawResult($result);

            if (count($result) !== 1) {
                throw new Exception\QueryDidNotReturnExactlyOneResult();
            }

            $value = $result[0];
            if (!is_int($value)) {
                throw new Exception\QueryDidNotReturnAnInt();
            }

            return $value;
        } catch (\Throwable $exception) {
            $trace->recordException($exception);

            throw $exception;
        }
    }

    /**
     * @param list<mixed>|array<string, mixed>                                        $parameters
     * @param array<int<0, max>|string, ArrayParameterType|ParameterType|Type|string> $parameterTypes
     *
     * @throws Exception\QueryDidNotReturnExactlyOneResult
     * @throws Exception\QueryDidNotReturnABoolean
     */
    #[\NoDiscard('as the result of the query would be lost')]
    public function fetchBool(
        string $sql,
        array $parameters = [],
        array $parameterTypes = [],
    ): bool {
        $trace = $this->tracer->startTrace(__METHOD__, $sql, $parameters, $parameterTypes, []);

        try {
            /** @var array<int, mixed> $result */
            $result = $this->connection->fetchFirstColumn($sql, $parameters, $parameterTypes);

            $trace->recordRawResult($result);

            if (count($result) !== 1) {
                throw new Exception\QueryDidNotReturnExactlyOneResult();
            }

            $value = $result[0];
            if (!is_bool($value)) {
                throw new Exception\QueryDidNotReturnABoolean();
            }

            return $value;
        } catch (\Throwable $exception) {
            $trace->recordException($exception);

            throw $exception;
        }
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
        $decoderTypeLevels = self::decoderTypeLevels($decoderTypes);

        foreach ($data as &$item) {
            self::decodeItemWithLevels($item, $decoderTypeLevels);
        }
    }

    /**
     * The keys of the decoder types can target nested values with a dotted path (e.g. "user.projects.*.name"). The same rules as for the
     * result transformer keys apply, @see DTO\ResultTransformerKey.
     *
     * @param array<string, mixed>           $item
     * @param array<string, DTO\DecoderType> $decoderTypes
     *
     * @internal
     */
    public static function decodeItem(
        array &$item,
        array $decoderTypes,
    ): void {
        self::decodeItemWithLevels($item, self::decoderTypeLevels($decoderTypes));
    }

    /**
     * Keys are sorted by their depth, so that a parent value (e.g. a JSON string) is always decoded before the values nested in it.
     *
     * @param array<string, DTO\DecoderType> $decoderTypes
     *
     * @return list<array{key: string, levels: non-empty-list<string>, decoderType: DTO\DecoderType}>
     */
    private static function decoderTypeLevels(array $decoderTypes): array
    {
        $decoderTypeLevels = [];
        foreach ($decoderTypes as $key => $decoderType) {
            $resultTransformerKey = new DTO\ResultTransformerKey($key);

            $decoderTypeLevels[] = [
                'key' => $resultTransformerKey->value,
                'levels' => explode('.', $resultTransformerKey->value),
                'decoderType' => $decoderType,
            ];
        }

        usort(
            $decoderTypeLevels,
            static fn (array $a, array $b): int => count($a['levels']) <=> count($b['levels']),
        );

        return $decoderTypeLevels;
    }

    /**
     * @param array<string, mixed>                                                                   $item
     * @param list<array{key: string, levels: non-empty-list<string>, decoderType: DTO\DecoderType}> $decoderTypeLevels
     *
     * @psalm-suppress ReferenceConstraintViolation Only values are replaced, the keys of the item stay the same
     */
    private static function decodeItemWithLevels(
        array &$item,
        array $decoderTypeLevels,
    ): void {
        foreach ($decoderTypeLevels as $decoderTypeLevel) {
            self::decodeRecursive(
                data: $item,
                key: $decoderTypeLevel['key'],
                levels: $decoderTypeLevel['levels'],
                levelIndex: 0,
                decoderType: $decoderTypeLevel['decoderType'],
            );
        }
    }

    /**
     * Missing keys and null values on the way to the value are ignored, the same way as missing keys on the first level are ignored.
     *
     * @param non-empty-list<string> $levels
     *
     * @psalm-suppress MixedAssignment Mixed is used here by design
     * @psalm-suppress MixedArgument Mixed is used here by design
     */
    private static function decodeRecursive(
        array &$data,
        string $key,
        array $levels,
        int $levelIndex,
        DTO\DecoderType $decoderType,
    ): void {
        $levelKey = $levels[$levelIndex];

        if ($levelKey === DTO\ResultTransformerKey::ARRAY_KEY_IDENTIFIER) {
            foreach ($data as &$element) {
                if ($element === null) {
                    continue;
                }
                if (!is_array($element)) {
                    throw new Exception\DecoderTypeKeyLevelIsNotAnArray($key, $levelKey);
                }

                self::decodeRecursive(
                    data: $element,
                    key: $key,
                    levels: $levels,
                    levelIndex: $levelIndex + 1,
                    decoderType: $decoderType,
                );
            }

            return;
        }

        if (!array_key_exists($levelKey, $data)) {
            return;
        }

        if ($levelIndex === count($levels) - 1) {
            $data[$levelKey] = self::decodeValue($data[$levelKey], $decoderType);

            return;
        }

        if ($data[$levelKey] === null) {
            return;
        }
        if (!is_array($data[$levelKey])) {
            throw new Exception\DecoderTypeKeyLevelIsNotAnArray($key, $levelKey);
        }

        self::decodeRecursive(
            data: $data[$levelKey],
            key: $key,
            levels: $levels,
            levelIndex: $levelIndex + 1,
            decoderType: $decoderType,
        );
    }

    public static function decodeValue(
        mixed $value,
        DTO\DecoderType $decoderType,
    ): mixed {
        return match ($decoderType) {
            DTO\DecoderType::BOOL => filter_var($value, FILTER_VALIDATE_BOOL),
            DTO\DecoderType::NULLABLE_BOOL => $value === null
                ? null
                : filter_var($value, FILTER_VALIDATE_BOOL),
            DTO\DecoderType::INT => filter_var($value, FILTER_VALIDATE_INT),
            DTO\DecoderType::NULLABLE_INT => $value === null
                ? null
                : filter_var($value, FILTER_VALIDATE_INT),
            DTO\DecoderType::FLOAT => filter_var($value, FILTER_VALIDATE_FLOAT),
            DTO\DecoderType::NULLABLE_FLOAT => $value === null
                ? null
                : filter_var($value, FILTER_VALIDATE_FLOAT),
            DTO\DecoderType::JSON => json_decode(
                $value,
                true,
                512,
                \JSON_THROW_ON_ERROR,
            ),
            DTO\DecoderType::NULLABLE_JSON => $value === null
                ? null
                : json_decode(
                    $value,
                    true,
                    512,
                    \JSON_THROW_ON_ERROR,
                ),
            DTO\DecoderType::JSON_WITH_EMPTY_ARRAY_ON_NULL => $value === null
                ? []
                : json_decode(
                    $value,
                    true,
                    512,
                    \JSON_THROW_ON_ERROR,
                ),
        };
    }
}
