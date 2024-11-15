<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Doctrine;

use DigitalCraftsman\DeserializingConnection\Serializer\StringNormalizable;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

abstract class StringNormalizableType extends Type
{
    abstract public static function getTypeName(): string;

    /** @return class-string<StringNormalizable> */
    abstract public static function getClass(): string;

    /**
     * @codeCoverageIgnore
     */
    protected function maxLength(): int
    {
        return 255;
    }

    /**
     * @codeCoverageIgnore
     */
    #[\Override]
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        $column['length'] = $this->maxLength();

        return $platform->getStringTypeDeclarationSQL($column);
    }

    /** @param ?string $value */
    #[\Override]
    public function convertToPHPValue($value, AbstractPlatform $platform): ?StringNormalizable
    {
        if ($value === null) {
            return null;
        }

        /** @var class-string<StringNormalizable> $class */
        $class = static::getClass();

        return $class::denormalize($value);
    }

    /** @param ?StringNormalizable $value */
    #[\Override]
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
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
