<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Serializer;

interface IntNormalizable
{
    public static function denormalize(int $int): self;

    public function normalize(): int;
}
