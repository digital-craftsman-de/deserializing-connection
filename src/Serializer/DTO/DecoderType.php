<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Serializer\DTO;

enum DecoderType
{
    case INT;
    case NULLABLE_INT;
    case FLOAT;
    case NULLABLE_FLOAT;
    case JSON;
    case NULLABLE_JSON;
    case JSON_WITH_EMPTY_ARRAY_ON_NULL;
}
