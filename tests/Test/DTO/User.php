<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Test\DTO;

use DigitalCraftsman\DeserializingConnection\Test\ValueObject\UserId;

final readonly class User
{
    public function __construct(
        public UserId $userId,
        public string $name,
    ) {
    }
}
