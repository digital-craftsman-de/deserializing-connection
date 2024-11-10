<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Doctrine;

use DigitalCraftsman\DeserializingConnection\Serializer\FloatNormalizable;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

abstract class FloatNormalizableType extends Type
{
    abstract public static function getTypeName(): string;

    /** @return class-string<FloatNormalizable> */
    abstract public static function getClass(): string;

    /**
     * @codeCoverageIgnore
     */
    #[\Override]
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getFloatDeclarationSQL($column);
    }

    /** @param ?float $value */
    #[\Override]
    public function convertToPHPValue($value, AbstractPlatform $platform): ?FloatNormalizable
    {
        if ($value === null) {
            return null;
        }

        /** @var class-string<FloatNormalizable> $class */
        $class = static::getClass();

        return $class::denormalize($value);
    }

    /** @param ?FloatNormalizable $value */
    #[\Override]
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?float
    {
        if ($value === null) {
            return null;
        }

        return $value->normalize();
    }

    /**
     * @codeCoverageIgnore
     */
    #[\Override]
    public function getName(): string
    {
        return static::getTypeName();
    }

    /**
     * @codeCoverageIgnore
     */
    #[\Override]
    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}