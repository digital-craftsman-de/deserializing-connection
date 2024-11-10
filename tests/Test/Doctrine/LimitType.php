<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Test\Doctrine;

use DigitalCraftsman\DeserializingConnection\Doctrine\IntNormalizableType;
use DigitalCraftsman\DeserializingConnection\Test\ValueObject\Limit;

final class LimitType extends IntNormalizableType
{
    #[\Override]
    public static function getTypeName(): string
    {
        return 'limit';
    }

    #[\Override]
    public static function getClass(): string
    {
        return Limit::class;
    }
}
