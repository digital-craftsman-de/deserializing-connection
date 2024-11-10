<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Test\ValueObject;

use DigitalCraftsman\DeserializingConnection\Serializer\IntNormalizable;

final readonly class Limit implements IntNormalizable
{
    public function __construct(
        public int $limit,
    ) {
        if ($this->limit < 0) {
            throw new \InvalidArgumentException('Limit can not be negative');
        }
        if ($this->limit > 1_000) {
            throw new \InvalidArgumentException('The limit can not be greater than 1000');
        }
    }

    // -- Int normalizable

    #[\Override]
    public static function denormalize(int $int): self
    {
        return new self($int);
    }

    #[\Override]
    public function normalize(): int
    {
        return $this->limit;
    }
}
