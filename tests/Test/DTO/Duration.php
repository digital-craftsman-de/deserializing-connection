<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Test\DTO;

use DigitalCraftsman\SelfAwareNormalizers\Serializer\IntNormalizable;

final readonly class Duration implements IntNormalizable
{
    public function __construct(
        public int $duration,
    ) {
    }

    // -- Int normalizable

    #[\Override]
    public static function denormalize(int $data): self
    {
        return new self($data);
    }

    #[\Override]
    public function normalize(): int
    {
        return $this->duration;
    }
}
