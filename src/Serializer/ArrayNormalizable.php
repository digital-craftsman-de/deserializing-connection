<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Serializer;

interface ArrayNormalizable
{
    public static function denormalize(array $array): self;

    public function normalize(): array;
}
