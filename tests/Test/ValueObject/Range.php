<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Test\ValueObject;

use DigitalCraftsman\DeserializingConnection\Serializer\FloatNormalizable;

final readonly class Range implements FloatNormalizable
{
    public function __construct(
        public float $range,
    ) {
        if ($this->range < 0) {
            throw new \InvalidArgumentException('The range can not be negative');
        }
        if ($this->range > 1) {
            throw new \InvalidArgumentException('The limit can not be greater than 1');
        }
    }

    // -- Float normalizable

    #[\Override]
    public static function denormalize(float $float): self
    {
        return new self($float);
    }

    #[\Override]
    public function normalize(): float
    {
        return $this->range;
    }
}
