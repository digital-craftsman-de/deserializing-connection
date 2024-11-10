<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Serializer\Exception;

use Symfony\Component\HttpFoundation\Response;

/**
 * @psalm-immutable
 */
final class ElementNotFound extends \DomainException
{
    public function __construct()
    {
        parent::__construct(
            'The item could not be found',
            Response::HTTP_NOT_FOUND,
        );
    }
}
