<?php

declare(strict_types=1);

namespace DigitalCraftsman\DeserializingConnection\Serializer\DTO;

final readonly class ResultTransformer
{
    /**
     * @param class-string|null                              $denormalizeResultToClass
     * @param \Closure(mixed $payload, array $result): mixed $transformer
     */
    public function __construct(
        public string $key,
        /**
         * @var class-string|null $denormalizeResultToClass
         */
        public ?string $denormalizeResultToClass,
        /**
         * @var \Closure(mixed $payload, array $result): mixed
         */
        public \Closure $transformer,
    ) {
    }
}
