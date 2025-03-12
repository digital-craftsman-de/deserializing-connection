<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Serializer;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final readonly class TypedDenormalizer
{
    public function __construct(
        private NormalizerInterface & DenormalizerInterface $serializer,
    ) {
    }

    public function normalize(
        object $object,
    ): array | string | int | float | bool | \ArrayObject | null {
        return $this->serializer->normalize(
            $object,
        );
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    public function denormalize(
        mixed $data,
        string $class,
    ): object {
        /** @var T */
        return $this->serializer->denormalize(
            data: $data,
            type: $class,
        );
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return list<T>
     */
    public function denormalizeArray(
        array $data,
        string $class,
    ): array {
        /** @var list<T> */
        return $this->serializer->denormalize(
            data: $data,
            type: self::arrayOfClass($class),
        );
    }

    /**
     * @internal
     */
    private static function arrayOfClass(string $class): string
    {
        return sprintf('%s[]', $class);
    }
}
