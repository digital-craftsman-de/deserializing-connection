<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Serializer;

use Doctrine\DBAL\Types\Type;

final readonly class DeserializingConnection
{
    public function __construct(
        private DecodingConnection $decodingConnection,
        private TypedDenormalizer $typedDenormalizer,
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
     *
     * @return T|null
     */
    public function findOne(
        string $sql,
        string $class,
        array $parameters = [],
        array $parameterTypes = [],
        array $decoderTypes = [],
    ): ?object {
        $result = $this->decodingConnection->fetchAssociative(
            $sql,
            $parameters,
            $parameterTypes,
            $decoderTypes,
        );

        if ($result === null) {
            return null;
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
     *
     * @return T
     */
    public function getOne(
        string $sql,
        string $class,
        array $parameters = [],
        array $parameterTypes = [],
        array $decoderTypes = [],
    ): ?object {
        $result = $this->decodingConnection->fetchAssociative(
            $sql,
            $parameters,
            $parameterTypes,
            $decoderTypes,
        );

        if ($result === null) {
            throw new Exception\ElementNotFound();
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
     *
     * @return list<T>
     */
    public function fetchArray(
        string $sql,
        string $class,
        array $parameters = [],
        array $parameterTypes = [],
        array $decoderTypes = [],
    ): array {
        /** @var list<array> $result */
        $result = $this->decodingConnection->fetchAllAssociative(
            $sql,
            $parameters,
            $parameterTypes,
            $decoderTypes,
        );

        return $this->typedDenormalizer->denormalizeArray($result, $class);
    }

    /**
     * @template T of object
     *
     * @param non-empty-string                                                     $sql
     * @param class-string<T>                                                      $class
     * @param list<mixed>|array<string, mixed>                                     $parameters
     * @param array<int, int|string|Type|null>|array<string, int|string|Type|null> $parameterTypes
     * @param array<string, DTO\DecoderType>                                       $decoderTypes
     *
     * @return \Generator<int, T>
     */
    public function fetchGenerator(
        string $sql,
        string $class,
        array $parameters = [],
        array $parameterTypes = [],
        array $decoderTypes = [],
    ): \Generator {
        /** @var list<array> $result */
        $result = $this->decodingConnection->fetchAllAssociative(
            $sql,
            $parameters,
            $parameterTypes,
            $decoderTypes,
        );

        foreach ($result as $item) {
            /** @var T */
            yield $this->typedDenormalizer->denormalize($item, $class);
        }
    }
}
