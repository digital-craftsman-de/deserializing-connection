<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Serializer\Exception;

/**
 * @psalm-immutable
 */
final class QueryDidNotReturnABoolean extends \DomainException
{
    public function __construct()
    {
        parent::__construct('The query did not return a boolean');
    }
}
