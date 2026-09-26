<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Serializer\Exception;

/**
 * @psalm-immutable
 */
final class DecoderTypeKeyLevelIsNotAnArray extends \InvalidArgumentException
{
    public function __construct(
        string $key,
        string $levelKey,
    ) {
        parent::__construct(
            sprintf(
                'The value of level "%s" of the decoder type key "%s" must be an array or null. Make sure the parent value is decoded (e.g. with DecoderType::JSON).',
                $levelKey,
                $key,
            ),
        );
    }
}
