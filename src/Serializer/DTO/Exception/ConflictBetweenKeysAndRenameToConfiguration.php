<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Serializer\DTO\Exception;

/**
 * @psalm-immutable
 */
final class ConflictBetweenKeysAndRenameToConfiguration extends \InvalidArgumentException
{
    public function __construct()
    {
        parent::__construct('There must not be any overlap between keys and renameTo configuration in any of the supplied result transformers.');
    }
}
