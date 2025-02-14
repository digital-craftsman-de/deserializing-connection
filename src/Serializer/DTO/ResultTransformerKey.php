<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Serializer\DTO;

final readonly class ResultTransformerKey
{
    public const string ARRAY_KEY_IDENTIFIER = '*';

    public function __construct(
        public string $value,
    ) {
        if (str_ends_with($this->value, self::ARRAY_KEY_IDENTIFIER)) {
            throw new Exception\ResultTransformationKeyCanNotEndWithAnArrayIdentifier($this->value);
        }
    }
}
