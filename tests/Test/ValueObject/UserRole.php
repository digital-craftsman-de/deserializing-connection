<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Test\ValueObject;

enum UserRole: string
{
    case ROLE_USER = 'ROLE_USER';
    case ROLE_ADMIN = 'ROLE_ADMIN';
}