<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Profiler\Exception;

/**
 * @psalm-immutable
 */
final class QueryCouldNotBeRendered extends \RuntimeException
{
    public function __construct(string $reason)
    {
        parent::__construct(sprintf(
            'The runnable query could not be rendered: %s',
            $reason,
        ));
    }
}
