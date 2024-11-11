<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Serializer;

interface StringNormalizable
{
    public static function denormalize(string $data): self;

    public function normalize(): string;
}
