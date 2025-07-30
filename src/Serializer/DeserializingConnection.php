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
     *
     * @param non-empty-string                                                     $sql
     * @param class-string<T>                                                      $class
     * @param list<mixed>|array<string, mixed>                                     $parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $parameterTypes
     * @param array<string, DTO\DecoderType>                                       $decoderTypes
     * @param array<int, DTO\ResultTransformer>                                    $resultTransformers
     * @param \Closure(mixed $item): string|null                                   $indexedBy
     *
     * @return list<T>
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
