<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Test\Doctrine;

use DigitalCraftsman\DeserializingConnection\Doctrine\FloatNormalizableType;
use DigitalCraftsman\DeserializingConnection\Test\ValueObject\Range;

final class RangeType extends FloatNormalizableType
{
    #[\Override]
    public static function getTypeName(): string
    {
        return 'range';
    }

    #[\Override]
    public static function getClass(): string
    {
        return Range::class;
    }
}
