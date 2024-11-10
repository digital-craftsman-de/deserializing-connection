<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Serializer;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class FloatNormalizableNormalizer implements NormalizerInterface, DenormalizerInterface
{
    #[\Override]
    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $data instanceof FloatNormalizable;
    }

    /** @param class-string $type */
    #[\Override]
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return class_exists($type)
            && is_subclass_of($type, FloatNormalizable::class);
    }

    /** @param FloatNormalizable|null $object */
    #[\Override]
    public function normalize(mixed $object, string $format = null, array $context = []): ?float
    {
        if ($object === null) {
            return null;
        }

        /** @var FloatNormalizable $object */
        return $object->normalize();
    }

    /**
     * @param float|null                      $data
     * @param class-string<FloatNormalizable> $type
     */
    #[\Override]
    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): ?FloatNormalizable
    {
        if ($data === null) {
            return null;
        }

        return $type::denormalize($data);
    }

    /**
     * @codeCoverageIgnore
     *
     * @return array<class-string, bool>
     */
    #[\Override]
    public function getSupportedTypes(?string $format): array
    {
        return [
            FloatNormalizable::class => true,
        ];
    }
}
