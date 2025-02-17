<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Serializer\Exception;

use Symfony\Component\HttpFoundation\Response;

/**
 * @psalm-immutable
 */
final class ResultTransformerKeyNotFound extends \DomainException
{
    public function __construct(string $key)
    {
        parent::__construct(
            sprintf(
                'The key "%s", for the result transformer was not found.',
                $key,
            ),
            Response::HTTP_NOT_FOUND,
        );
    }
}
