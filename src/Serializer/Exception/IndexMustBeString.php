<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Serializer\Exception;

/**
 * @psalm-immutable
 */
final class IndexMustBeString extends \InvalidArgumentException
{
    public function __construct()
    {
        parent::__construct('The index returned from the indexedBy function must be a string.');
    }
}
