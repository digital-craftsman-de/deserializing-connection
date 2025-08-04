<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Serializer;

use Doctrine\DBAL\Types\Type;

final readonly class DeserializingConnection
{
    public function __construct(
        private DecodingConnection $decodingConnection,
        private TypedDenormalizer $typedDenormalizer,
        private ResultTransformerRunner $resultTransformerRunner,
    ) {
    }

    /**
     * Can be used to get a single object from the database, returns null if no result is found. Use @see getOne instead if you want to
     * throw an exception when no result is found.
     *
     * The denormalization step expects an array with every column being a key in the array. If instead you need a single value (will be
     * most likely scalar and not an array) instead, you can use the @see findOneFromSingleValue method instead.
     *
     * @template T of object
     *
     * @param non-empty-string                                                     $sql
     * @param class-string<T>                                                      $class
     * @param list<mixed>|array<string, mixed>                                     $parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $parameterTypes
     * @param array<string, DTO\DecoderType>                                       $decoderTypes
     * @param array<int, DTO\ResultTransformer>                                    $resultTransformers
     *
     * @return T|null
     */
    public function findOne(
        string $sql,
        string $class,
        array $parameters = [],
        array $parameterTypes = [],
        array $decoderTypes = [],
        array $resultTransformers = [],
    ): ?object {
        $resultTransformerDTOs = new DTO\ResultTransformers($resultTransformers);

        $result = $this->decodingConnection->fetchAssociative(
            $sql,
            $parameters,
            $parameterTypes,
            $decoderTypes,
        );

        if ($result === null) {
            return null;
        }

        $this->resultTransformerRunner->runTransformations(
            result: $result,
            resultTransformers: $resultTransformerDTOs,
        );

        return $this->typedDenormalizer->denormalize($result, $class);
    }

    /**
     * Can be used to get a single object from the database, but throws an exception if no result is found. Use @see findOne if you want to
     * return null when no result is found.
     *
     * The denormalization step expects an array with every column being a key in the array. If instead you need a single value (will be
     * most likely scalar and not an array) instead, you can use the @see getOneFromSingleValue method instead.
     *
     * @template T of object
     *
     * @param non-empty-string                                                     $sql
     * @param class-string<T>                                                      $class
     * @param list<mixed>|array<string, mixed>                                     $parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $parameterTypes
     * @param array<string, DTO\DecoderType>                                       $decoderTypes
     * @param array<int, DTO\ResultTransformer>                                    $resultTransformers
     *
     * @return T
     */
    public function getOne(
        string $sql,
        string $class,
        array $parameters = [],
        array $parameterTypes = [],
        array $decoderTypes = [],
        array $resultTransformers = [],
    ): ?object {
        $resultTransformerDTOs = new DTO\ResultTransformers($resultTransformers);

        $result = $this->decodingConnection->fetchAssociative(
            $sql,
            $parameters,
            $parameterTypes,
            $decoderTypes,
        );

        if ($result === null) {
            throw new Exception\ElementNotFound();
        }

        $this->resultTransformerRunner->runTransformations(
            result: $result,
            resultTransformers: $resultTransformerDTOs,
        );

        return $this->typedDenormalizer->denormalize($result, $class);
    }

    /**
     * Can be used to get a single object from the database, returns null when no result is found. Use @see getOneFromSingleValue instead if
     * you want to throw an exception when no result is found.
     *
     * The denormalization step expects a single value (will be most likely scalar and not an array). If instead you need an array with
     * every column being a key in the array, you can use the @see findOne method instead.
     *
     * @template T of object
     *
     * @param non-empty-string                                                     $sql
     * @param class-string<T>                                                      $class
     * @param list<mixed>|array<string, mixed>                                     $parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $parameterTypes
     *
     * @return T|null
     */
    public function findOneFromSingleValue(
        string $sql,
        string $class,
        array $parameters = [],
        array $parameterTypes = [],
        ?DTO\DecoderType $decoderType = null,
        ?DTO\ResultTransformer $resultTransformer = null,
    ): ?object {
        if ($resultTransformer !== null
            && $resultTransformer->renameTo !== null
        ) {
            throw new Exception\SingleValueTransformationMustNotContainRenaming();
        }

        $result = $this->decodingConnection->fetchOne(
            $sql,
            $parameters,
            $parameterTypes,
            $decoderType,
        );

        if ($result === null) {
            return null;
        }

        if ($resultTransformer !== null) {
            $result = $this->resultTransformerRunner->transformItem(
                transformer: $resultTransformer,
                item: $result,
                result: $result,
                resultOfLevel: $result,
            );
        }

        return $this->typedDenormalizer->denormalize($result, $class);
    }

    /**
     * Can be used to get a single object from the database, but throws an exception if no result is found. Use @see findOneFromSingleValue
     * instead if you want to return null when no result is found.
     *
     * The denormalization step expects a single value (will be most likely scalar and not an array). If instead you need an array with
     * every column being a key in the array, you can use the @see getOne method instead.
     *
     * @template T of object
     *
     * @param non-empty-string                                                     $sql
     * @param class-string<T>                                                      $class
     * @param list<mixed>|array<string, mixed>                                     $parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $parameterTypes
     *
     * @return T
     */
    public function getOneFromSingleValue(
        string $sql,
        string $class,
        array $parameters = [],
        array $parameterTypes = [],
        ?DTO\DecoderType $decoderType = null,
        ?DTO\ResultTransformer $resultTransformer = null,
    ): object {
        if ($resultTransformer !== null
            && $resultTransformer->renameTo !== null
        ) {
            throw new Exception\SingleValueTransformationMustNotContainRenaming();
        }

        $result = $this->decodingConnection->fetchOne(
            $sql,
            $parameters,
            $parameterTypes,
            $decoderType,
        );

        if ($result === null) {
            throw new Exception\ElementNotFound();
        }

        if ($resultTransformer !== null) {
            $result = $this->resultTransformerRunner->transformItem(
                transformer: $resultTransformer,
                item: $result,
                result: $result,
                resultOfLevel: $result,
            );
        }

        return $this->typedDenormalizer->denormalize($result, $class);
    }

    /**
     * @template T of object
     * @template I of \Closure(mixed $item): string|null
     *
     * @param non-empty-string                                                     $sql
     * @param class-string<T>                                                      $class
     * @param list<mixed>|array<string, mixed>                                     $parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $parameterTypes
     * @param array<string, DTO\DecoderType>                                       $decoderTypes
     * @param array<int, DTO\ResultTransformer>                                    $resultTransformers
     * @param I                                                                    $indexedBy
     *
     * @return (I is null ? list<T> : array<string, T>)
     */
    public function findArray(
        string $sql,
        string $class,
        array $parameters = [],
        array $parameterTypes = [],
        array $decoderTypes = [],
        array $resultTransformers = [],
        ?\Closure $indexedBy = null,
    ): array {
        $resultTransformerDTOs = new DTO\ResultTransformers($resultTransformers);

        $result = $this->decodingConnection->fetchAllAssociative(
            $sql,
            $parameters,
            $parameterTypes,
            $decoderTypes,
        );

        foreach ($result as &$resultItem) {
            $this->resultTransformerRunner->runTransformations(
                result: $resultItem,
                resultTransformers: $resultTransformerDTOs,
            );
        }

        $denormalizedResult = $this->typedDenormalizer->denormalizeArray($result, $class);

        if ($indexedBy === null) {
            return $denormalizedResult;
        }

        $indexedResult = [];
        foreach ($denormalizedResult as $item) {
            $index = $indexedBy($item);
            /**
             * @psalm-suppress TypeDoesNotContainType Validation against missing compliance with Psalm.
             */
            if (!is_string($index)) {
                throw new Exception\IndexMustBeString();
            }

            $indexedResult[$index] = $item;
        }

        return $indexedResult;
    }

    /**
     * @template T of object
     *
     * @param non-empty-string                                                     $sql
     * @param class-string<T>                                                      $class
     * @param list<mixed>|array<string, mixed>                                     $parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $parameterTypes
     * @param array<string, DTO\DecoderType>                                       $decoderTypes
     * @param array<int, DTO\ResultTransformer>                                    $resultTransformers
     *
     * @return \Generator<int, T>
     */
    public function findGenerator(
        string $sql,
        string $class,
        array $parameters = [],
        array $parameterTypes = [],
        array $decoderTypes = [],
        array $resultTransformers = [],
    ): \Generator {
        $resultTransformerDTOs = new DTO\ResultTransformers($resultTransformers);

        /** @var list<array> $result */
        $result = $this->decodingConnection->fetchAllAssociative(
            $sql,
            $parameters,
            $parameterTypes,
            $decoderTypes,
        );

        foreach ($result as &$resultItem) {
            $this->resultTransformerRunner->runTransformations(
                result: $resultItem,
                resultTransformers: $resultTransformerDTOs,
            );
        }

        foreach ($result as $item) {
            /** @var T */
            yield $this->typedDenormalizer->denormalize($item, $class);
        }
    }
}
