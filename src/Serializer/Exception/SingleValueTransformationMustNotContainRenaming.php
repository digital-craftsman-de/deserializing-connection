<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Serializer\Exception;

/**
 * @psalm-immutable
 */
final class SingleValueTransformationMustNotContainRenaming extends \DomainException
{
    public function __construct()
    {
        parent::__construct('A transformer for a single value must not contain a renameTo configuration.');
    }
}
