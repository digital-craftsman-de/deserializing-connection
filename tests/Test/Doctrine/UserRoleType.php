<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Test\Doctrine;

use DigitalCraftsman\DeserializingConnection\Doctrine\StringEnumType;
use DigitalCraftsman\DeserializingConnection\Test\ValueObject\UserRole;

final class UserRoleType extends StringEnumType
{
    #[\Override]
    public static function getTypeName(): string
    {
        return 'user_role';
    }

    #[\Override]
    public static function getClass(): string
    {
        return UserRole::class;
    }
}
