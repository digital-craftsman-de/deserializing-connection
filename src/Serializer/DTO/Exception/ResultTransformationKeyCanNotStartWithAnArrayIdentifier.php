<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Serializer\DTO\Exception;

use Symfony\Component\HttpFoundation\Response;

/**
 * @psalm-immutable
 */
final class ResultTransformationKeyCanNotStartWithAnArrayIdentifier extends \DomainException
{
    public function __construct(string $key)
    {
        parent::__construct(
            sprintf(
                'The key "%s" can not start with an array identifier.',
                $key,
            ),
            Response::HTTP_NOT_FOUND,
        );
    }
}
