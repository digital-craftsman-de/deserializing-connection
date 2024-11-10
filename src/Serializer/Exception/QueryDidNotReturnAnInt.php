<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Serializer\Exception;

/**
 * @psalm-immutable
 */
final class QueryDidNotReturnAnInt extends \DomainException
{
    public function __construct()
    {
        parent::__construct('The query did not return a int.');
    }
}
