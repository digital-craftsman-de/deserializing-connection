<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Serializer\Exception;

/**
 * @psalm-immutable
 */
final class ItemToDenormalizeIsObject extends \InvalidArgumentException
{
    public function __construct(
        string $denormalizeResultToClass,
        string $itemClass,
    ) {
        parent::__construct(
            sprintf(
                'The item to denormalize to class "%s" must not be an object (class: %s).',
                $denormalizeResultToClass,
                $itemClass,
            ),
        );
    }
}
